<?php

declare(strict_types=1);

namespace App\Domain\SampleData;

use App\Domain\Source\Source;
use App\Domain\TickData;

/**
 * Transforms to OHLCV series.
 * Aggregates per seconds by default.
 */
interface SampleDataTransformer
{
    public function fromPayload(Source $source, array $payload, int $timeFrame = 1, bool $splitSeries = false): TickData;
}
