<?php

namespace App\Support\Payments;

class PayloadHasher
{
    public static function hashForLink(array $payload, int $linkId): string
    {
        $normalized = self::normalize($payload);
        $raw = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', $linkId.'|'.($raw ?: '{}'));
    }

    private static function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(static fn ($item) => self::normalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = self::normalize($item);
        }

        return $value;
    }
}
