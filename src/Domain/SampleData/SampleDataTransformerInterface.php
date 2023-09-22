<?php

namespace App\Domain\SampleData;

use App\Domain\Source\Source;

interface SampleDataTransformerInterface
{
    public function transform(Source $source, \ArrayIterator $tickData): \ArrayIterator;

    public function setNext(SampleDataTransformerInterface $transformer): SampleDataTransformerInterface;
}
