<?php

declare(strict_types=1);

namespace App\Infra\SampleData\SampleDataTransformers;

use App\Domain\SampleData\SampleDataMapper;
use App\Domain\SampleData\SampleDataTransformerInterface;
use App\Domain\Source\Source;

/**
 * Select only fields we need.
 */
class ColumnFilterSampleDataTransformer implements SampleDataTransformerInterface
{
    private ?SampleDataTransformerInterface $nextTransformer = null;
    private SampleDataMapper $dataMapper;

    public function __construct(SampleDataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    public function transform(Source $source, \ArrayIterator $tickData): \ArrayIterator
    {
        $newIterator = new \ArrayIterator();
        foreach ($tickData as $sample) {
            $newSample = $this->dataMapper->mapToTs(source: $source, sample: $sample);
            $newIterator->append($newSample);
        }

        if (null !== $this->nextTransformer) {
            return $this->nextTransformer->transform(source: $source, tickData: $newIterator);
        }

        return $tickData;
    }

    public function setNext(SampleDataTransformerInterface $transformer): SampleDataTransformerInterface
    {
        $this->nextTransformer = $transformer;

        return $transformer;
    }
}
