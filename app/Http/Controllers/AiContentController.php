<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\Product;
use App\Models\Store;
use App\Services\AiContentService;
use App\Services\AiCreditService;
use App\Services\AiImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class AiContentController extends Controller
{
    public function __construct(
        private readonly AiContentService $aiContentService,
        private readonly AiImageService $aiImageService,
        private readonly AiCreditService $aiCreditService,
    ) {
    }

    public function generate(Request $request): JsonResponse
    {
        return $this->generateWithAi(
            $request,
            AiContentService::TYPES,
            config('services.openai.model'),
            fn (Store $store, string $type, array $context) => $this->aiContentService->generate($store, $type, $context),
        );
    }

    public function generateImage(Request $request): JsonResponse
    {
        return $this->generateWithAi(
            $request,
            AiContentService::IMAGE_TYPES,
            config('services.openai.image_model'),
            fn (Store $store, string $type, array $context) => $this->aiImageService->generate($store, $type, $context),
        );
    }

    private function generateWithAi(Request $request, array $allowedTypes, ?string $model, callable $generator): JsonResponse
    {
        $validated = $request->validate($this->rules($allowedTypes));
        $store = $this->storeForRequest($request, $validated);

        abort_unless($store, 404);
        abort_unless($store->allowsAiContent(), 403);

        $type = $validated['type'];
        $context = $this->context($validated, $store);
        try {
            $this->validateGenerationContext($type, $context);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'ai_credits' => $this->creditsPayload($store, $type),
            ], 422);
        }

        $generation = $this->createGeneration($request, $store, $type, $context, $validated['topic'] ?? null, $model);
        $creditsCharged = false;

        try {
            $this->aiCreditService->consume($store, $type, $generation, $request->user()?->id);
            $creditsCharged = true;

            $result = $generator($store, $type, $context);
            $content = array_merge($result['content'] ?? [], [
                'ai_credits' => $this->creditsPayload($store, $type),
            ]);
            $raw = $result['raw'] ?? [];

            $generation->update([
                'response' => $content,
                'status' => 'completed',
                'input_tokens' => $raw['usage']['input_tokens'] ?? null,
                'output_tokens' => $raw['usage']['output_tokens'] ?? null,
            ]);

            return response()->json($content);
        } catch (RuntimeException $exception) {
            if ($creditsCharged) {
                $this->aiCreditService->refund($store, $type, $generation, $request->user()?->id);
            }

            $generation->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
                'ai_credits' => $this->creditsPayload($store, $type),
            ], $this->errorStatus($exception));
        }
    }

    private function rules(array $allowedTypes): array
    {
        return [
            'type' => ['required', 'string', Rule::in($allowedTypes)],
            'store_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'name' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:120'],
            'material' => ['nullable', 'string', 'max:120'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'features' => ['nullable', 'string', 'max:2000'],
            'topic' => ['nullable', 'string', 'max:240'],
        ];
    }

    private function createGeneration(
        Request $request,
        Store $store,
        string $type,
        array $context,
        ?string $prompt,
        ?string $model,
    ): AiGeneration {
        return AiGeneration::create([
            'store_id' => $store->id,
            'user_id' => $request->user()?->id,
            'type' => $type,
            'prompt' => $prompt,
            'context' => $context,
            'status' => 'processing',
            'provider' => 'openai',
            'model' => $model,
        ]);
    }

    private function creditsPayload(Store $store, string $type): array
    {
        return [
            'cost' => $this->aiCreditService->cost($type),
            'balance' => $this->aiCreditService->balance($store),
        ];
    }

    private function errorStatus(RuntimeException $exception): int
    {
        return str_contains($exception->getMessage(), 'creditos IA suficientes') ? 402 : 422;
    }

    private function storeForRequest(Request $request, array $validated): ?Store
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        if (! empty($validated['product_id'])) {
            $product = Product::with('store')->find($validated['product_id']);

            if (! $product) {
                return null;
            }

            if (! $user->isAdmin() && (int) $product->store?->user_id !== (int) $user->id) {
                return null;
            }

            return $product->store;
        }

        if ($user->isAdmin() && ! empty($validated['store_id'])) {
            return Store::find($validated['store_id']);
        }

        if (! empty($validated['store_id'])) {
            return $user->stores()->whereKey($validated['store_id'])->first();
        }

        return $user->store ?? $user->stores()->first();
    }

    private function context(array $validated, Store $store): array
    {
        $product = null;

        if (! empty($validated['product_id'])) {
            $product = Product::where('store_id', $store->id)->find($validated['product_id']);
        }

        return [
            'producto' => [
                'nombre' => $validated['name'] ?? $product?->name,
                'categoria' => $validated['category'] ?? $product?->category,
                'material' => $validated['material'] ?? $product?->material,
                'precio' => $validated['price'] ?? $product?->price,
                'descripcion_actual' => $validated['description'] ?? $product?->description,
                'caracteristicas_actuales' => $validated['features'] ?? $product?->features,
            ],
            'solicitud' => $validated['topic'] ?? null,
            'categorias_tienda' => $store->allowsCategories() ? $store->productCategoryOptions() : [],
        ];
    }

    private function validateGenerationContext(string $type, array $context): void
    {
        if ($type === AiContentService::ANNOUNCEMENT) {
            return;
        }

        if ($type === AiContentService::STORE_COVER_IMAGE) {
            return;
        }

        $product = $context['producto'] ?? [];
        $name = trim((string) ($product['nombre'] ?? ''));
        $category = trim((string) ($product['categoria'] ?? ''));
        $description = trim(strip_tags((string) ($product['descripcion_actual'] ?? '')));
        $material = trim((string) ($product['material'] ?? ''));

        if ($type === AiContentService::PRODUCT_NAME && $name === '') {
            throw new RuntimeException('Agrega primero el nombre actual del producto para que la IA pueda mejorarlo.');
        }

        if (in_array($type, [
            AiContentService::PRODUCT_DESCRIPTION,
            AiContentService::PRODUCT_BADGES,
            AiContentService::PRODUCT_FEATURES,
            AiContentService::PRODUCT_IMAGE,
        ], true) && $name === '' && $category === '' && $description === '' && $material === '') {
            throw new RuntimeException('Agrega primero el nombre, categoria o detalles del producto para generar contenido con IA.');
        }
    }
}
