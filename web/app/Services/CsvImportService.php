<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CopperPriceRepository;

final class CsvImportService
{
    public function __construct(private ?CopperPriceRepository $prices = null)
    {
        $this->prices ??= new CopperPriceRepository();
    }

    public function import(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('Unable to open uploaded CSV.');
        }

        $headers = array_map(fn ($h) => $this->header((string) $h), fgetcsv($handle) ?: []);
        $stats = ['total_rows' => 0, 'valid_rows' => 0, 'imported_rows' => 0, 'updated_rows' => 0, 'invalid_rows' => 0];

        while (($values = fgetcsv($handle)) !== false) {
            $stats['total_rows']++;
            if (count($headers) !== count($values)) {
                $stats['invalid_rows']++;
                continue;
            }
            $raw = array_combine($headers, $values);
            $row = $this->normalize($raw ?: []);
            if (!$row) {
                $stats['invalid_rows']++;
                continue;
            }
            $stats['valid_rows']++;
            $result = $this->prices->upsert($row);
            $stats[$result === 'inserted' ? 'imported_rows' : 'updated_rows']++;
        }
        fclose($handle);

        return $stats;
    }

    private function normalize(array $row): ?array
    {
        $date = $this->value($row, ['date', 'tanggal']);
        $close = $this->value($row, ['close', 'price', 'terakhir']);
        if (!$date || $close === null) {
            return null;
        }

        $normalizedDate = $this->date($date);
        $closeValue = $this->number($close);
        if (!$normalizedDate || $closeValue === null) {
            return null;
        }

        return [
            'date' => $normalizedDate,
            'open' => $this->number($this->value($row, ['open', 'pembukaan'])),
            'high' => $this->number($this->value($row, ['high', 'tertinggi'])),
            'low' => $this->number($this->value($row, ['low', 'terendah'])),
            'close' => $closeValue,
            'volume' => $this->volume($this->value($row, ['volume', 'vol', 'vol.'])),
            'change_percent' => $this->number($this->value($row, ['change %', 'change_percent', 'change'])),
        ];
    }

    private function header(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        return strtolower(trim($value, " \t\n\r\0\x0B\"'"));
    }

    private function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return null;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        $formats = ['Y-m-d', 'M d, Y', 'F d, Y', 'm/d/Y', 'd/m/Y', 'd-m-Y', 'm-d-Y'];
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $raw);
            if ($date instanceof \DateTimeImmutable && $date->format($format) === $raw) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($raw);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function volume(mixed $value): ?int
    {
        $number = $this->number($value);
        return $number === null ? null : (int) round($number);
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = strtoupper(trim(str_replace(['%', ' '], '', (string) $value)));
        if ($clean === '' || $clean === '-') {
            return null;
        }

        $multiplier = 1;
        $suffix = substr($clean, -1);
        if (in_array($suffix, ['K', 'M', 'B'], true)) {
            $multiplier = ['K' => 1_000, 'M' => 1_000_000, 'B' => 1_000_000_000][$suffix];
            $clean = substr($clean, 0, -1);
        }

        $clean = str_replace(',', '', $clean);
        return is_numeric($clean) ? (float) $clean * $multiplier : null;
    }
}
