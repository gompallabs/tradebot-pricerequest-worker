<?php

declare(strict_types=1);

namespace App\Domain\SampleData;

use App\Domain\Source\Source;

interface SampleChecker
{
    public function check(Source $source, array $sample);

    public function supports(Source $source);
}
