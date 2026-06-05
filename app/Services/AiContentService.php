<?php

namespace App\Services;

use App\Models\Store;
use App\Services\Concerns\ConfiguresOpenAiHttp;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AiContentService
{
    use ConfiguresOpenAiHttp;

    public const PRODUCT_DESCRIPTION = 'product_description';
    public const PRODUCT_NAME = 'product_name';
    public const PRODUCT_BADGES = 'product_badges';
    public const PRODUCT_FEATURES = 'product_features';
    public const ANNOUNCEMENT = 'announcement';
    public const STORE_COVER_IMAGE = 'store_cover_image';
    public const PRODUCT_IMAGE = 'product_image';

    public const TYPES = [
        self::PRODUCT_DESCRIPTION,
        self::PRODUCT_NAME,
        self::PRODUCT_BADGES,
        self::PRODUCT_FEATURES,
        self::ANNOUNCEMENT,
    ];

    public const IMAGE_TYPES = [
        self::STORE_COVER_IMAGE,
        self::PRODUCT_IMAGE,
    ];

    public function generate(Store $store, string $type, array $context): array
    {
        $apiKey = (string) config('services.openai.key');

        if ($apiKey === '') {
            throw new RuntimeException('Configura OPENAI_API_KEY para usar el asistente IA.');
        }

        $prompt = $this->prompt($store, $type, $context);
        $model = (string) config('services.openai.model', 'gpt-4.1-mini');

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions($this->openAiHttpOptions())
                ->timeout(25)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'instructions' => $this->instructions(),
                    'input' => $prompt,
                    'max_output_tokens' => 500,
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message') ?: 'No se pudo generar contenido con IA.';
            throw new RuntimeException($message);
        }

        return $this->normalizeOutput($type, $this->responseText($response), $response);
    }

    private function instructions(): string
    {
        return implode(' ', [
            'Eres un asistente de ecommerce para tiendas colombianas.',
            'Escribe en espanol claro, vendedor y profesional.',
            'No inventes descuentos, garantia, stock, envios ni metodos de pago si no aparecen en el contexto.',
            'Devuelve solo JSON valido, sin markdown.',
        ]);
    }

    private function prompt(Store $store, string $type, array $context): string
    {
        $payload = [
            'tienda' => [
                'nombre' => $store->name,
                'tipo' => $store->businessTypeLabel(),
                'ubicacion' => $store->location,
                'descripcion' => $store->shop_copy,
            ],
            'tarea' => $type,
            'contexto' => $context,
            'formato' => $this->expectedFormat($type),
        ];

        return 'Genera contenido para esta tienda usando este JSON: ' . json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function expectedFormat(string $type): array
    {
        return match ($type) {
            self::PRODUCT_NAME => ['name' => 'Nombre corto, comercial y sin comillas.'],
            self::PRODUCT_BADGES => ['badges' => ['3 etiquetas cortas, maximo 18 caracteres cada una.']],
            self::PRODUCT_FEATURES => ['features' => ['5 caracteristicas o beneficios concretos, cortos y verificables.']],
            self::ANNOUNCEMENT => ['announcements' => ['3 avisos promocionales breves, maximo 90 caracteres cada uno.']],
            default => ['description' => 'Descripcion de producto de 70 a 120 palabras.'],
        };
    }

    private function normalizeOutput(string $type, string $text, array $rawResponse): array
    {
        if (trim($text) === '') {
            throw new RuntimeException('La IA no devolvio contenido. Intenta de nuevo.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            $decoded = $this->fallbackOutput($type, $text);
        }

        return [
            'content' => match ($type) {
                self::PRODUCT_NAME => ['name' => Str::limit(trim((string) ($decoded['name'] ?? '')), 80, '')],
                self::PRODUCT_BADGES => ['badges' => $this->cleanList($decoded['badges'] ?? [], 3, 18)],
                self::PRODUCT_FEATURES => ['features' => $this->cleanList($decoded['features'] ?? [], 6, 90)],
                self::ANNOUNCEMENT => ['announcements' => $this->cleanList($decoded['announcements'] ?? [], 3, 90)],
                default => ['description' => trim((string) ($decoded['description'] ?? ''))],
            },
            'raw' => $rawResponse,
        ];
    }

    private function fallbackOutput(string $type, string $text): array
    {
        $text = trim($text);

        return match ($type) {
            self::PRODUCT_NAME => ['name' => $text],
            self::PRODUCT_BADGES => ['badges' => preg_split('/[,;\n]+/', $text) ?: []],
            self::PRODUCT_FEATURES => ['features' => preg_split('/[,;\n]+/', $text) ?: []],
            self::ANNOUNCEMENT => ['announcements' => preg_split('/\n+/', $text) ?: []],
            default => ['description' => $text],
        };
    }

    private function cleanList(mixed $items, int $limit, int $maxLength): array
    {
        return collect(is_array($items) ? $items : [])
            ->map(fn ($item) => Str::limit(trim((string) $item), $maxLength, ''))
            ->filter()
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    private function responseText(array $response): string
    {
        if (isset($response['output_text'])) {
            return trim((string) $response['output_text']);
        }

        foreach (($response['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    return trim((string) $content['text']);
                }
            }
        }

        return '';
    }
}
