<?php

declare(strict_types=1);

namespace App\Infra\SampleData;

use App\Domain\SampleData\SampleDataMapper;
use App\Domain\SampleData\SampleDataTransformer as TickDataTransformerInterface;
use App\Domain\Source\Source;
use App\Domain\TickData;
use App\Domain\TimeFormat;
use App\Infra\SampleData\SampleDataTransformers\ChronoSortSampleDataTransformer;
use App\Infra\SampleData\SampleDataTransformers\ColumnFilterSampleDataTransformer;
use App\Infra\SampleData\SampleDataTransformers\OhlcSampleDataTransformer;
use App\Infra\SampleData\SampleDataTransformers\TickToBucketSampleDataTransformer;

class SampleDataTransformer implements TickDataTransformerInterface
{
    private SampleDataMapper $dataMapper;
    private TimeFormat $timeFormat;

    public function __construct(
        SampleDataMapper $dataMapper,
        TimeFormat $timeFormat
    ) {
        $this->dataMapper = $dataMapper;
        $this->timeFormat = $timeFormat;
    }

    public function fromPayload(Source $source, array $payload, int $timeFrame = 1, bool $splitSeries = false): TickData
    {
        $transformer = new ColumnFilterSampleDataTransformer($this->dataMapper);
        $transformer
            ->setNext(new ChronoSortSampleDataTransformer())
            ->setNext(new TickToBucketSampleDataTransformer(
                timeFormat: $this->timeFormat,
                timeFrame: $timeFrame
            ))
            ->setNext(new OhlcSampleDataTransformer($this->timeFormat));

        return new TickData($transformer->transform(source: $source, tickData: new \ArrayIterator($payload)));
    }
}
