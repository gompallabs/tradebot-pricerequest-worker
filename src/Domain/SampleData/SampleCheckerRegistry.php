<?php

declare(strict_types=1);

namespace App\Domain\SampleData;

use App\Domain\Source\Source;

interface SampleCheckerRegistry
{
    public function getCheckerForSource(Source $source): SampleChecker;
}
