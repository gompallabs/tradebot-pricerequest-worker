<?php

declare(strict_types=1);

namespace App\Infra\SampleData\SampleDataTransformers;

use App\Domain\PriceOhlcv;
use App\Domain\SampleData\SampleDataTransformerInterface;
use App\Domain\Source\Source;

class OhlcSampleDataTransformer implements SampleDataTransformerInterface
{
    private ?SampleDataTransformerInterface $nextTransformer = null;

    public function transform(Source $source, \ArrayIterator $tickData): \ArrayIterator
    {
        $current = $tickData->current();
        $tsms = (int) (floor($current[0]) * 1000);
        $candleData = new \ArrayIterator();

        $ohclv = new PriceOhlcv($tsms, $current[2]);
        $ohclv->addBuyVolume($current[3]);
        $ohclv->addSellVolume($current[4]);

        $tickData->next();
        while ($tickData->valid()) {
            $ohclv->addTickWithoutLabel($tickData->current());
            $tickData->next();
        }
        $candleData->append($ohclv);

        if (null !== $this->nextTransformer) {
            return $this->nextTransformer->transform(source: $source, tickData: $candleData);
        }

        return $candleData;
    }

    public function setNext(SampleDataTransformerInterface $transformer): SampleDataTransformerInterface
    {
        $this->nextTransformer = $transformer;

        return $transformer;
    }
}
