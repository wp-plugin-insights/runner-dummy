<?php

declare(strict_types=1);

namespace WpPluginInsights\RunnerDummy;

use InvalidArgumentException;

class Job
{
    public function __construct(
        public readonly string $plugin,
        public readonly string $src
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $plugin = $payload['plugin'] ?? null;
        $src = $payload['src'] ?? null;

        if (!is_string($plugin) || trim($plugin) === '') {
            throw new InvalidArgumentException('Missing or invalid "plugin" field.');
        }

        if (!is_string($src) || trim($src) === '') {
            throw new InvalidArgumentException('Missing or invalid "src" field.');
        }

        return new self(trim($plugin), trim($src));
    }
}
