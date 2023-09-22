<?php

declare(strict_types=1);

namespace App\Infra\SampleData\SampleChecker;

use App\Domain\SampleData\SampleChecker;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;
use Assert\Assert;

class BybitApiSampleChecker implements SampleChecker
{
    public function check(Source $source, array $sample)
    {
        if ($this->supports($source)) {
            Assert::lazy()
                ->that($sample)
                ->isArray(' must be an array')
                ->keyExists('timestamp', 'must have a timestamp key')
                ->keyExists('side', 'must have a timestamp key')
                ->keyExists('size', 'must have a timestamp key')
                ->keyExists('price', 'must have a timestamp key')
                ->verifyNow();

            return true;
        }

        throw new \LogicException();
    }

    public function supports(Source $source): bool
    {
        return
            'Bybit' === $source->getExchange()->name
            && SourceType::RestApi === $source->getSourceType();
    }
}
