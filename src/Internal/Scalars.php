<?php

declare(strict_types=1);

namespace OtpId\Internal;

/**
 * Type-safe scalar extraction from decoded JSON payloads, mirroring Go's
 * json.Unmarshal zero-value semantics: a missing (or wrong-typed) key
 * yields the type's zero value instead of a decoding error. Used by every
 * result class's fromArray() — not part of the public API.
 */
final class Scalars
{
    /**
     * @param array<string, mixed> $data
     */
    public static function str(array $data, string $key): string
    {
        return \is_string($data[$key] ?? null) ? $data[$key] : '';
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function int(array $data, string $key): int
    {
        return \is_int($data[$key] ?? null) ? $data[$key] : 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function bool(array $data, string $key): bool
    {
        return \is_bool($data[$key] ?? null) ? $data[$key] : false;
    }

    private function __construct()
    {
        // Static helper holder — not instantiable.
    }
}
