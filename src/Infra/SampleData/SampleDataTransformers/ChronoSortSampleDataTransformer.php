<?php

declare(strict_types=1);

namespace App\Infra\SampleData\SampleDataTransformers;

use App\Domain\SampleData\SampleDataTransformerInterface;
use App\Domain\Source\Source;

class ChronoSortSampleDataTransformer implements SampleDataTransformerInterface
{
    private ?SampleDataTransformerInterface $nextTransformer = null;

    public function transform(Source $source, \ArrayIterator $tickData): \ArrayIterator
    {
        $tickData->rewind();
        $tickData->uasort(
            function ($a, $b) {
                if ($a[0] === $b[0]) {
                    return 0;
                }

                return ($a[0] < $b[0]) ? -1 : 1;
            }
        );
        if (null !== $this->nextTransformer) {
            return $this->nextTransformer->transform(source: $source, tickData: $tickData);
        }

        return $tickData;
    }

    public function setNext(SampleDataTransformerInterface $transformer): SampleDataTransformerInterface
    {
        $this->nextTransformer = $transformer;

        return $transformer;
    }
}
