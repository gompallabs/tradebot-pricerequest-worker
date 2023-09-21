<?php

declare(strict_types=1);

namespace App\Domain\SampleData;

use App\Domain\Source\Source;

interface SampleDataMapperRegistry
{
    public function getMapperForSource(Source $source): ?SampleDataMapper;
}
