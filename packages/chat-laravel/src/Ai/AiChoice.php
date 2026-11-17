<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\InvalidChat;

/**
 * Picks the responder. Echo when neither SDK is installed.
 * One installed SDK is used. Both installed SDKs require DETERMLY_CHAT_AI.
 */
final class AiChoice
{
    public const AUTO = 'auto';

    public const ECHO = 'echo';

    public const LARAVEL = 'laravel-ai';

    public const PRISM = 'prism';

    public static function resolve(string $configured, bool $laravelInstalled, bool $prismInstalled): string
    {
        $configured = strtolower(trim($configured));
        if ($configured === '') {
            $configured = self::AUTO;
        }

        if ($configured === self::ECHO) {
            return self::ECHO;
        }
        if ($configured === self::LARAVEL) {
            if (! $laravelInstalled) {
                throw new InvalidChat('DETERMLY_CHAT_AI is laravel-ai, but laravel/ai is not installed.');
            }

            return self::LARAVEL;
        }
        if ($configured === self::PRISM) {
            if (! $prismInstalled) {
                throw new InvalidChat('DETERMLY_CHAT_AI is prism, but prism-php/prism is not installed.');
            }

            return self::PRISM;
        }
        if ($configured !== self::AUTO) {
            throw new InvalidChat('DETERMLY_CHAT_AI must be auto, echo, laravel-ai, or prism.');
        }
        if ($laravelInstalled && $prismInstalled) {
            throw new InvalidChat('Both laravel/ai and prism-php/prism are installed. Set DETERMLY_CHAT_AI to laravel-ai or prism.');
        }
        if ($laravelInstalled) {
            return self::LARAVEL;
        }
        if ($prismInstalled) {
            return self::PRISM;
        }

        return self::ECHO;
    }

    public static function present(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
