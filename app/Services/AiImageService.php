<?php

namespace App\Services;

use App\Models\Store;
use App\Services\Concerns\ConfiguresOpenAiHttp;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AiImageService
{
    use ConfiguresOpenAiHttp;

    public function generate(Store $store, string $type, array $context): array
    {
        $apiKey = (string) config('services.openai.key');

        if ($apiKey === '') {
            throw new RuntimeException('Configura OPENAI_API_KEY para generar imagenes con IA.');
        }

        $prompt = $this->prompt($store, $type, $context);
        $model = (string) config('services.openai.image_model', 'gpt-image-1');

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions($this->openAiHttpOptions())
                ->timeout(60)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => $model,
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => $this->size($type),
                    'quality' => config('services.openai.image_quality', 'low'),
                    'output_format' => 'webp',
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message') ?: 'No se pudo generar la imagen con IA.';
            throw new RuntimeException($message);
        }

        $image = (string) ($response['data'][0]['b64_json'] ?? '');

        if ($image === '') {
            throw new RuntimeException('La IA no devolvio una imagen. Intenta de nuevo.');
        }

        $bytes = base64_decode($image, true);

        if ($bytes === false) {
            throw new RuntimeException('La imagen generada no se pudo procesar.');
        }

        $path = $this->path($type, $store);
        Storage::disk('public')->put($path, $bytes);

        return [
            'content' => [
                'image_path' => $path,
                'image_url' => asset('storage/' . $path),
                'prompt' => $prompt,
            ],
            'raw' => $response,
        ];
    }

    private function prompt(Store $store, string $type, array $context): string
    {
        $business = $store->businessTypeLabel();

        if ($type === AiContentService::STORE_COVER_IMAGE) {
            return implode(' ', [
                'Crea una portada horizontal profesional para una tienda online colombiana.',
                'Estilo ecommerce moderno, limpio, realista, con buena iluminacion y espacio visual para texto.',
                'No incluyas texto, logos, marcas registradas, personas reconocibles ni marcas de agua.',
                "Nombre de tienda: {$store->name}. Tipo de negocio: {$business}.",
                'Descripcion de tienda: ' . ((string) ($store->shop_copy ?: 'Catalogo online profesional.')),
            ]);
        }

        $product = $context['producto'] ?? [];

        return implode(' ', [
            'Crea una imagen cuadrada tipo ecommerce para producto.',
            'Producto centrado, fondo claro, sombra suave, acabado profesional de catalogo.',
            'No incluyas texto, logos, marcas registradas, personas reconocibles ni marcas de agua.',
            'Nombre: ' . ((string) ($product['nombre'] ?? 'Producto')),
            'Categoria: ' . ((string) ($product['categoria'] ?? 'General')),
            'Material o detalle: ' . ((string) ($product['material'] ?? '')),
            'Descripcion: ' . Str::limit((string) ($product['descripcion_actual'] ?? ''), 500),
        ]);
    }

    private function size(string $type): string
    {
        return $type === AiContentService::STORE_COVER_IMAGE ? '1536x1024' : '1024x1024';
    }

    private function path(string $type, Store $store): string
    {
        $directory = $type === AiContentService::STORE_COVER_IMAGE ? 'stores/ai' : 'products/ai';

        return $directory . '/' . $store->id . '-' . Str::uuid() . '.webp';
    }
}
