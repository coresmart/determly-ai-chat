<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery;

use Determly\Chat\Laravel\Ai\ConfigurationResponder;
use Determly\Chat\Laravel\Ai\LaravelAiResponder;
use Determly\Chat\Laravel\Ai\PrismResponder;
use Determly\Chat\Run\Responder;
use Determly\Chat\ToolRegistry;

/**
 * The two gallery pages that send the visitor's sentence to a model.
 * The model chooses the tool. The runner executes it. The model writes the answer.
 */
final class ModelChat
{
    public const LARAVEL = 'laravel-ai';

    public const PRISM = 'prism';

    /**
     * @var array<string, string>
     */
    private const KEYS = [
        'openai' => 'OPENAI_API_KEY',
        'anthropic' => 'ANTHROPIC_API_KEY',
        'gemini' => 'GEMINI_API_KEY',
        'groq' => 'GROQ_API_KEY',
        'mistral' => 'MISTRAL_API_KEY',
        'xai' => 'XAI_API_KEY',
        'deepseek' => 'DEEPSEEK_API_KEY',
        'openrouter' => 'OPENROUTER_API_KEY',
        'perplexity' => 'PERPLEXITY_API_KEY',
        'elevenlabs' => 'ELEVENLABS_API_KEY',
        'voyageai' => 'VOYAGEAI_API_KEY',
    ];

    public static function handles(string $slug): bool
    {
        return $slug === self::LARAVEL || $slug === self::PRISM;
    }

    public static function library(string $slug): string
    {
        return $slug === self::PRISM ? 'Prism' : 'Laravel AI';
    }

    public static function instructions(): string
    {
        return <<<'TEXT'
You answer questions about this shop's orders. You do not know the orders until a tool returns them. Call lookup_order with the order id when the visitor names one. Call query_orders when you need to find an order by customer, status, or total. status is all, open, paid, or shipped. Answer in your own words from the tool result. If the tool does not contain the order, say so. Do not refund an order and do not invent a row.
TEXT;
    }

    public static function make(string $slug, ToolRegistry $tools, ?string $provider, ?string $model): Responder
    {
        $library = self::library($slug);
        $problem = self::problem($library, $provider, $model);
        if ($problem !== null) {
            return new ConfigurationResponder($problem);
        }

        $advertised = self::advertise($tools);

        return $slug === self::PRISM
            ? new PrismResponder($advertised, self::instructions(), $provider, $model)
            : new LaravelAiResponder($advertised, self::instructions(), $provider, $model);
    }

    /**
     * @return array{library: string, provider: string, model: string, key: string, ready: bool, missing: string}
     */
    public static function describe(string $slug, ?string $provider, ?string $model): array
    {
        $library = self::library($slug);
        $key = self::keyName($provider);

        return [
            'library' => $library,
            'provider' => $provider ?? '',
            'model' => $model ?? '',
            'key' => $key ?? '',
            'ready' => self::problem($library, $provider, $model) === null,
            'missing' => self::problem($library, $provider, $model) ?? '',
        ];
    }

    private static function problem(string $library, ?string $provider, ?string $model): ?string
    {
        if ($provider === null || $model === null) {
            return 'This page calls '.$library.'. Set DETERMLY_CHAT_PROVIDER and DETERMLY_CHAT_MODEL. The reply is not scripted.';
        }
        $key = self::keyName($provider);
        if ($key !== null && self::envValue($key) === '') {
            return 'This page calls '.$library.' with '.$provider.' / '.$model.'. Set '.$key.'. The reply is not scripted.';
        }

        return null;
    }

    private static function advertise(ToolRegistry $tools): ToolRegistry
    {
        $advertised = new ToolRegistry;
        foreach ($tools->all() as $tool) {
            if (in_array($tool->name(), ['lookup_order', 'query_orders'], true)) {
                $advertised->register($tool, 'gallery');
            }
        }

        return $advertised;
    }

    private static function keyName(?string $provider): ?string
    {
        if ($provider === null) {
            return null;
        }

        return self::KEYS[strtolower($provider)] ?? null;
    }

    private static function envValue(string $name): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) ? trim($value) : '';
    }
}
