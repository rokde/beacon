<?php

declare(strict_types=1);

namespace Beacon\Dashboard\Enums;

enum TileSize: string
{
    case Small = 'sm';
    case Medium = 'md';
    case Large = 'lg';

    public function cssClass(): string
    {
        return "beacon-tile--{$this->value}";
    }

    /** Grid column span hint for the layout */
    public function gridSpan(): int
    {
        return match ($this) {
            self::Small  => 1,
            self::Medium => 2,
            self::Large  => 4,
        };
    }
}
