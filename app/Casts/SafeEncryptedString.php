<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Same as Laravel's "encrypted" string cast, but returns null if decryption fails
 * (e.g. ciphertext from another APP_KEY after DB import) instead of throwing.
 */
class SafeEncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable $e) {
            Log::warning('Could not decrypt model attribute; re-save the value or fix APP_KEY.', [
                'model' => $model::class,
                'attribute' => $key,
            ]);

            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [$key => null];
        }

        return [$key => Crypt::encryptString($value)];
    }
}
