<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class SensitiveColumnPolicy
{
    public static function isSensitive(string $column): bool
    {
        $normalized = strtolower(trim($column));
        if ($normalized === '') {
            return true;
        }

        if (in_array($normalized, [
            'password',
            'password_confirmation',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'recovery_codes',
        ], true)) {
            return true;
        }

        $tokens = preg_split('/[^a-z0-9]+/', $normalized) ?: [];
        $sensitiveTokens = ['password', 'passwd', 'passphrase', 'secret', 'token', 'tokens', 'remember', 'recovery', 'otp'];

        foreach ($tokens as $token) {
            if (in_array($token, $sensitiveTokens, true)) {
                return true;
            }
        }

        return false;
    }
}
