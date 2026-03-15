<?php

namespace App\Support\Payments;

class JsonPathExtractor
{
    public function get(array $payload, ?string $path, mixed $default = null): mixed
    {
        $values = $this->all($payload, $path);

        return $values[0] ?? $default;
    }

    /**
     * @return array<int, mixed>
     */
    public function all(array $payload, ?string $path): array
    {
        $path = trim((string) $path);

        if ($path === '') {
            return [];
        }

        $segments = array_values(array_filter(explode('.', $path), static fn ($segment) => $segment !== ''));
        if ($segments === []) {
            return [];
        }

        return $this->walk($payload, $segments, 0);
    }

    /**
     * @param  array<int, string>  $segments
     * @return array<int, mixed>
     */
    private function walk(mixed $current, array $segments, int $index): array
    {
        if ($index >= count($segments)) {
            return [$current];
        }

        $segment = $segments[$index];

        if ($segment === '*') {
            if (! is_array($current)) {
                return [];
            }

            $values = [];
            foreach ($current as $item) {
                $values = [...$values, ...$this->walk($item, $segments, $index + 1)];
            }

            return $values;
        }

        if (! is_array($current) || ! array_key_exists($segment, $current)) {
            return [];
        }

        return $this->walk($current[$segment], $segments, $index + 1);
    }
}
