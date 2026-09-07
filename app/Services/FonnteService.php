<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FonnteService
{
    private const BASE_URL = 'https://api.fonnte.com';

    public function send(Institution $institution, string $target, string $message): array
    {
        $response = $this->request($institution)
            ->asForm()
            ->post(self::BASE_URL.'/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '0',
                'connectOnly' => true,
            ]);

        return $this->result($response);
    }

    public function deviceProfile(Institution $institution): array
    {
        $response = $this->request($institution)
            ->asForm()
            ->post(self::BASE_URL.'/device');

        return $this->result($response);
    }

    private function request(Institution $institution)
    {
        return Http::acceptJson()
            ->withHeaders(['Authorization' => (string) $institution->fonnte_token])
            ->connectTimeout(5)
            ->timeout(12);
    }

    private function result(Response $response): array
    {
        $payload = $response->json();
        $payload = is_array($payload) ? $payload : ['raw' => $response->body()];
        $status = $payload['status'] ?? $payload['Status'] ?? false;

        return [
            'ok' => $response->successful() && filter_var($status, FILTER_VALIDATE_BOOL),
            'http_status' => $response->status(),
            'data' => $payload,
            'error' => $payload['reason'] ?? $payload['detail'] ?? null,
        ];
    }
}
