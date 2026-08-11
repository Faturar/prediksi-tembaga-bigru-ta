<?php

declare(strict_types=1);

namespace App\Services;

final class MlApiClient
{
    private array $config;

    public function __construct()
    {
        $this->config = config('app');
    }

    public function health(): array
    {
        return $this->request('GET', '/health');
    }

    public function train(array $payload): array
    {
        return $this->request('POST', '/api/train', $payload);
    }

    public function predict(array $payload): array
    {
        return $this->request('POST', '/api/predict', $payload);
    }

    public function model(string $version): array
    {
        return $this->request('GET', '/api/models/' . rawurlencode($version));
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $ch = curl_init(rtrim($this->config['ml_api_url'], '/') . $path);
        $headers = ['Accept: application/json', 'X-API-Key: ' . $this->config['ml_api_key']];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['ml_api_timeout'],
            CURLOPT_CUSTOMREQUEST => $method,
        ]);
        if ($method !== 'GET') {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status >= 400) {
            $message = $error ?: "ML API request failed with HTTP {$status}";
            if (is_string($body) && $body !== '') {
                $decodedError = json_decode($body, true);
                if (is_array($decodedError) && isset($decodedError['detail'])) {
                    $detail = $decodedError['detail'];
                    $message = is_scalar($detail) ? (string) $detail : json_encode($detail);
                }
            }
            throw new \RuntimeException($message);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('ML API returned invalid JSON.');
        }
        return $decoded;
    }
}
