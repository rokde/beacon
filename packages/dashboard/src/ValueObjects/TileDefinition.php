<?php

declare(strict_types=1);

namespace Beacon\Dashboard\ValueObjects;

use Beacon\Core\Enums\Granularity;
use Beacon\Dashboard\Enums\TileSize;

/**
 * Immutable configuration for a single dashboard tile.
 *
 * Created via the fluent Tile builder:
 *   Tile::kpi('new_registrations')
 *       ->label('New Registrations')
 *       ->size(TileSize::Medium)
 *       ->comparison(Comparison::previousPeriod())
 *       ->showForecast(horizon: '30 days')
 */
final class TileDefinition
{
    /** @var list<Comparison> */
    private array $comparisons = [];

    private function __construct(
        public readonly string $kpiKey,
        private string $label = '',
        private TileSize $size = TileSize::Medium,
        private Granularity $granularity = Granularity::Day,
        private bool $forecast = false,
        private string $forecastHorizon = '30 days',
        private ?string $chartType = null,
        private int $chartHeight = 200,
        private int $periodDays = 30,
    ) {}

    public static function kpi(string $kpiKey): self
    {
        return new self(kpiKey: $kpiKey, label: $kpiKey);
    }

    // ── Fluent setters (immutable) ──────────────────────────────────────────

    public function label(string $label): self
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function size(TileSize $size): self
    {
        $clone = clone $this;
        $clone->size = $size;

        return $clone;
    }

    public function granularity(Granularity $granularity): self
    {
        $clone = clone $this;
        $clone->granularity = $granularity;

        return $clone;
    }

    public function comparison(Comparison $comparison): self
    {
        $clone = clone $this;
        $clone->comparisons = [$comparison];

        return $clone;
    }

    /**
     * @param  list<Comparison>  $comparisons
     */
    public function comparisons(array $comparisons): self
    {
        $clone = clone $this;
        $clone->comparisons = $comparisons;

        return $clone;
    }

    public function showForecast(string $horizon = '30 days'): self
    {
        $clone = clone $this;
        $clone->forecast = true;
        $clone->forecastHorizon = $horizon;

        return $clone;
    }

    public function chart(string $type = 'line', int $height = 200): self
    {
        $clone = clone $this;
        $clone->chartType = $type;
        $clone->chartHeight = $height;

        return $clone;
    }

    public function period(int $days): self
    {
        $clone = clone $this;
        $clone->periodDays = $days;

        return $clone;
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSize(): TileSize
    {
        return $this->size;
    }

    public function getGranularity(): Granularity
    {
        return $this->granularity;
    }

    public function hasForecast(): bool
    {
        return $this->forecast;
    }

    public function getForecastHorizon(): string
    {
        return $this->forecastHorizon;
    }

    public function getChartType(): ?string
    {
        return $this->chartType;
    }

    public function getChartHeight(): int
    {
        return $this->chartHeight;
    }

    public function getPeriodDays(): int
    {
        return $this->periodDays;
    }

    /** @return list<Comparison> */
    public function getComparisons(): array
    {
        return $this->comparisons;
    }

    public function hasChart(): bool
    {
        return $this->chartType !== null || $this->size !== TileSize::Small;
    }
}
