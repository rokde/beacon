<?php

declare(strict_types=1);

namespace Beacon\Dashboard\ValueObjects;

use Closure;

/**
 * Immutable configuration for a single dashboard page.
 *
 * Usage:
 *   DashboardDefinition::make('sales')
 *       ->label('Sales Overview')
 *       ->path('/sales')
 *       ->authorize(fn ($user) => $user->hasRole('sales_manager'))
 *       ->refreshInterval(300)
 *       ->tiles([...])
 */
final class DashboardDefinition
{
    /** @var list<TileDefinition> */
    private array $tiles = [];

    private function __construct(
        public readonly string $id,
        private string $label = '',
        private string $path = '',
        private ?Closure $authCallback = null,
        private int $refreshInterval = 300,
    ) {
        $this->label = $id;
        $this->path = "/{$id}";
    }

    public static function make(string $id): self
    {
        return new self(id: $id);
    }

    // ── Fluent setters ──────────────────────────────────────────────────────

    public function label(string $label): self
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function path(string $path): self
    {
        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    public function authorize(Closure $callback): self
    {
        $clone = clone $this;
        $clone->authCallback = $callback;

        return $clone;
    }

    public function refreshInterval(int $seconds): self
    {
        $clone = clone $this;
        $clone->refreshInterval = $seconds;

        return $clone;
    }

    /**
     * @param  list<TileDefinition>  $tiles
     */
    public function tiles(array $tiles): self
    {
        $clone = clone $this;
        $clone->tiles = $tiles;

        return $clone;
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getRefreshInterval(): int
    {
        return $this->refreshInterval;
    }

    public function getAuthCallback(): ?Closure
    {
        return $this->authCallback;
    }

    /** @return list<TileDefinition> */
    public function getTiles(): array
    {
        return $this->tiles;
    }

    public function isAuthorized(mixed $user): bool
    {
        if ($this->authCallback === null) {
            return true;
        }

        return (bool) ($this->authCallback)($user);
    }

    /**
     * Full route path including base path prefix.
     * Resolved by the router using the configured base_path.
     */
    public function fullPath(string $basePath): string
    {
        return rtrim($basePath, '/').'/'.ltrim($this->path, '/');
    }
}
