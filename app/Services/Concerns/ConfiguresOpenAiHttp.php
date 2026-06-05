<?php

namespace App\Services\Concerns;

trait ConfiguresOpenAiHttp
{
    private function openAiHttpOptions(): array
    {
        $proxy = trim((string) config('services.openai.proxy'));

        if ($proxy !== '') {
            return ['proxy' => $proxy];
        }

        $curl = [];

        if (defined('CURLOPT_PROXY')) {
            $curl[\CURLOPT_PROXY] = '';
        }

        if (defined('CURLOPT_NOPROXY')) {
            $curl[\CURLOPT_NOPROXY] = '*';
        }

        return $curl === [] ? [] : ['curl' => $curl];
    }
}
