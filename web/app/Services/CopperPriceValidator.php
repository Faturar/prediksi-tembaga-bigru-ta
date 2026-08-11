<?php

declare(strict_types=1);

namespace App\Services;

final class CopperPriceValidator
{
    public function validate(array $input): array
    {
        $errors = [];
        $row = [
            'date' => trim((string) ($input['date'] ?? '')),
            'open' => $this->optionalNumber($input['open'] ?? null),
            'high' => $this->optionalNumber($input['high'] ?? null),
            'low' => $this->optionalNumber($input['low'] ?? null),
            'close' => $this->requiredNumber($input['close'] ?? null),
            'volume' => $this->optionalNumber($input['volume'] ?? null),
            'change_percent' => $this->optionalNumber($input['change_percent'] ?? null),
        ];

        if ($row['date'] === '' || !$this->validDate($row['date'])) {
            $errors[] = 'Tanggal wajib diisi dengan format tanggal yang valid.';
        }

        foreach (['open' => 'Open', 'high' => 'High', 'low' => 'Low'] as $key => $label) {
            if ($this->provided($input[$key] ?? null) && $row[$key] === null) {
                $errors[] = "{$label} harus berupa angka.";
            } elseif ($row[$key] !== null && $row[$key] <= 0) {
                $errors[] = "{$label} harus lebih besar dari 0.";
            }
        }

        if ($row['close'] === null) {
            $errors[] = 'Close wajib diisi dan harus berupa angka.';
        } elseif ($row['close'] <= 0) {
            $errors[] = 'Close harus lebih besar dari 0.';
        }

        if ($this->provided($input['volume'] ?? null) && $row['volume'] === null) {
            $errors[] = 'Volume harus berupa angka.';
        } elseif ($row['volume'] !== null && $row['volume'] < 0) {
            $errors[] = 'Volume tidak boleh negatif.';
        }

        if ($this->provided($input['change_percent'] ?? null) && $row['change_percent'] === null) {
            $errors[] = 'Change % harus berupa angka.';
        }

        if ($row['high'] !== null && $row['low'] !== null && $row['high'] < $row['low']) {
            $errors[] = 'High tidak boleh lebih kecil dari Low.';
        }

        if ($row['high'] !== null) {
            if ($row['open'] !== null && $row['high'] < $row['open']) {
                $errors[] = 'High tidak boleh lebih kecil dari Open.';
            }
            if ($row['close'] !== null && $row['high'] < $row['close']) {
                $errors[] = 'High tidak boleh lebih kecil dari Close.';
            }
        }

        if ($row['low'] !== null) {
            if ($row['open'] !== null && $row['low'] > $row['open']) {
                $errors[] = 'Low tidak boleh lebih besar dari Open.';
            }
            if ($row['close'] !== null && $row['low'] > $row['close']) {
                $errors[] = 'Low tidak boleh lebih besar dari Close.';
            }
        }

        if ($row['volume'] !== null) {
            $row['volume'] = (int) round($row['volume']);
        }

        return [$row, $errors];
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function requiredNumber(mixed $value): ?float
    {
        return $this->provided($value) && is_numeric($value) ? (float) $value : null;
    }

    private function optionalNumber(mixed $value): ?float
    {
        return $this->provided($value) && is_numeric($value) ? (float) $value : null;
    }

    private function provided(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }
}
