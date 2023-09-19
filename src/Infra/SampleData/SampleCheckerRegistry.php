<?php

declare(strict_types=1);

namespace App\Infra\SampleData;

use App\Domain\SampleData\SampleChecker;
use App\Domain\SampleData\SampleCheckerRegistry as SampleCheckerRegistryInterface;
use App\Domain\Source\Source;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class SampleCheckerRegistry implements SampleCheckerRegistryInterface
{
    private array $checkers;

    public function __construct(
        #[TaggedIterator('app.sample_checker')] iterable $checkers
    ) {
        foreach ($checkers['arguments'][0]->getIterator() as $checker) {
            $this->checkers[] = $checker;
        }
    }

    public function check(Source $source, array $sample)
    {
        $checker = $this->getCheckerForSource($source);

        return $checker->check($source, $sample);
    }

    public function supports(Source $source): bool
    {
        $checker = $this->getCheckerForSource($source);

        return $checker->supports($source);
    }

    public function getCheckerForSource(Source $source): SampleChecker
    {
        /** @var SampleChecker $checker */
        foreach ($this->checkers as $checker) {
            if ($checker->supports($source)) {
                return $checker;
            }
        }
        throw new \RuntimeException(sprintf('Missing checker for %s of type %s in '.__METHOD__, $source->getExchange()->name, $source->getSourceType()->name));
    }
}
