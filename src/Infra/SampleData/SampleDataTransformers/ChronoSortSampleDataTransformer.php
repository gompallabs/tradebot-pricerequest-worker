<?php

declare(strict_types=1);

namespace App\Infra\SampleData\SampleDataTransformers;

use App\Domain\SampleData\SampleDataTransformerInterface;
use App\Domain\Source\Source;

/**
 * Make sûre the data is sorted.
 */
class ChronoSortSampleDataTransformer implements SampleDataTransformerInterface
{
    private ?SampleDataTransformerInterface $nextTransformer = null;

    public function transform(Source $source, \ArrayIterator $tickData): \ArrayIterator
    {
        $data = iterator_to_array($tickData);
        usort(
            $data,
            function ($sampleA, $sampleB) {
                $a = (float) $sampleA[0];
                $b = (float) $sampleB[0];

                if ($a === $b) {
                    return 0;
                }

                return ($a < $b) ? -1 : 1;
            }
        );
        $tickData = new \ArrayIterator($data);

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
