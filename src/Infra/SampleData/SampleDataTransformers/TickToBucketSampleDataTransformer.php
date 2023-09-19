<?php

declare(strict_types=1);

namespace App\Infra\SampleData\SampleDataTransformers;

use App\Domain\SampleData\SampleDataTransformerInterface;
use App\Domain\Source\Source;
use App\Domain\TimeFormat;
use App\Domain\TimeFormatTools;

class TickToBucketSampleDataTransformer implements SampleDataTransformerInterface
{
    private ?SampleDataTransformerInterface $nextTransformer = null;

    public function __construct(
        private readonly TimeFormat $timeFormat,
        private readonly int $timeFrame
    ) {
    }

    /**
     * Read entire dataset only once.
     */
    public function transform(Source $source, \ArrayIterator $tickData): \ArrayIterator
    {
        $start = $tickData->offsetGet(0);
        $end = $tickData->offsetGet($tickData->count() - 1);
        $tsScale = TimeFormatTools::scale(
            start: $start[0],
            end: $end[0],
            timeFormat: $this->timeFormat,
            stepSize: $this->timeFrame
        );

        $ohclv = new \ArrayIterator();
        foreach ($tsScale as $timeStep) {
            $bucket = new \ArrayIterator();
            while ($tickData->valid() && $tickData->current()[0] < $timeStep + 1) {
                $bucket->append($tickData->current());
                $tickData->next();
            }
            if ($bucket->count() > 0) {
                $result = $this->nextTransformer->transform(source: $source, tickData: $bucket);
                $ohclv->append($result->current()); // subsequent handlers return one result per bucket
            }
        }

        return $ohclv;
    }

    public function setNext(SampleDataTransformerInterface $transformer): SampleDataTransformerInterface
    {
        $this->nextTransformer = $transformer;

        return $transformer;
    }
}
