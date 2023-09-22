<?php

declare(strict_types=1);

namespace App\Infra\SampleData\SampleDataTransformers;

use _PHPStan_95cdbe577\Symfony\Component\Console\Exception\LogicException;
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
     * Group By buckets representing one timeframe (ex: 1 second).
     */
    public function transform(Source $source, \ArrayIterator $tickData): \ArrayIterator
    {
        $tickData->rewind();
        $start = $tickData->offsetGet(0);
        $end = $tickData->offsetGet($tickData->count() - 1);

        [$tsScale, $step] = TimeFormatTools::scale(
            start: $start[0],
            end: $end[0],
            timeFormat: $this->timeFormat,
            stepSize: $this->timeFrame
        );

        $multiplicator = match ($this->timeFormat->value) {
            'DotMilliseconds', 'Seconds' => 1000,
            'IntMilliseconds' => 1,
            'default' => throw new LogicException('Missing case '.$this->timeFormat->value)
        };

        $ohclv = new \ArrayIterator();
        $n = count($tsScale);

        for ($i = 0; $i < $n; ++$i) {
            $bucket = new \ArrayIterator();
            $timeStep = $tsScale[$i];
            while ($tickData->valid() && $tickData->current()[0] < $timeStep + $step) {
                $bucket->append($tickData->current());
                $tickData->next();
            }

            if ($bucket->count() > 0) {
                $result = $this->nextTransformer->transform(source: $source, tickData: $bucket);
                $bucketTsms = (int) ($multiplicator * $timeStep);
                $priceOhlcv = $result->current();
                $priceOhlcv->setTsms($bucketTsms);
                $ohclv->append($priceOhlcv);
            }
        }

        $ohclv->rewind();

        return $ohclv;
    }

    public function setNext(SampleDataTransformerInterface $transformer): SampleDataTransformerInterface
    {
        $this->nextTransformer = $transformer;

        return $transformer;
    }
}
