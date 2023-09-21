<?php

declare(strict_types=1);

namespace App\Infra\SampleData\SampleDataTransformers;

use App\Domain\SampleData\SampleDataTransformerInterface;
use App\Domain\Source\Source;

class VolumeSampleDataTransformer implements SampleDataTransformerInterface
{
    private ?SampleDataTransformerInterface $nextTransformer = null;

    public function transform(Source $source, \ArrayIterator $tickData): \ArrayIterator
    {
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
