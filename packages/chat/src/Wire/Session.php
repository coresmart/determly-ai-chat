<?php

declare(strict_types=1);

namespace Determly\Chat\Wire;

final class Session
{
    /**
     * @param  callable(Emitter): void  $handler
     */
    public static function run(
        Writer $writer,
        callable $handler,
        string $agentId = 'virtual',
        string $agentName = 'Assistant',
        ?\Closure $id = null,
        ?\Closure $clock = null,
        ?string $executionId = null,
    ): void {
        $ids = $id ?? self::randomId();
        $now = $clock ?? self::isoNow();
        $started = $now();
        $emit = new Emitter(
            $writer,
            $executionId ?? $ids('exec'),
            $ids('turn'),
            $agentId,
            $agentName,
            $started,
            $ids,
            $now,
        );
        $emit->open();

        try {
            $handler($emit);
            $emit->complete();
        } catch (\Throwable $error) {
            $emit->error($error->getMessage());
        }
    }

    private static function randomId(): \Closure
    {
        return static fn (string $prefix): string => $prefix.'_'.bin2hex(random_bytes(16));
    }

    private static function isoNow(): \Closure
    {
        return static function (): string {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            return $now->format('Y-m-d\TH:i:s.v\Z');
        };
    }
}
