<?php

declare(strict_types=1);

namespace App\Services;

final class PredictionWindowService
{
    public function build(array $orderedRows, int $windowSize): array
    {
        if ($windowSize < 1) {
            throw new \InvalidArgumentException('Window size model tidak valid.');
        }

        usort($orderedRows, fn (array $a, array $b): int => strcmp((string) $a['date'], (string) $b['date']));

        if (count($orderedRows) < $windowSize) {
            throw new \LengthException("Data historis belum mencukupi. Model membutuhkan minimal {$windowSize} observasi harga penutupan untuk melakukan prediksi.");
        }

        $window = array_slice($orderedRows, -1 * $windowSize);

        return array_values($window);
    }

    public function context(array $orderedRows, ?array $model): ?array
    {
        if (!$model) {
            return null;
        }

        $windowSize = (int) $model['window_size'];
        $latest = $orderedRows === [] ? null : $orderedRows[array_key_last($orderedRows)];
        $window = count($orderedRows) >= $windowSize ? $this->build($orderedRows, $windowSize) : [];

        return [
            'model_version' => $model['version'],
            'window_size' => $windowSize,
            'latest_date' => $latest['date'] ?? null,
            'input_start_date' => $window[0]['date'] ?? null,
            'input_end_date' => $window === [] ? null : $window[array_key_last($window)]['date'],
            'available_records' => count($orderedRows),
            'has_enough_data' => count($orderedRows) >= $windowSize,
        ];
    }
}
