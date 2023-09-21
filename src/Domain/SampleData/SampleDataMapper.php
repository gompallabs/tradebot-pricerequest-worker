<?php

declare(strict_types=1);

namespace App\Domain\SampleData;

use App\Domain\Source\Source;

interface SampleDataMapper
{
    public function mapToTs(Source $source, array $sample): \SplFixedArray;

    public function supports(Source $source): bool;

    public function getTimeStampFieldName(Source $source): string;

    public function getColumns(Source $source): array;
}
