<?php

declare(strict_types=1);

namespace App\Infra\SampleData;

use App\Domain\SampleData\SampleDataMapper;
use App\Domain\SampleData\SampleDataMapperRegistry as SampleDataMapperRegistryInterface;
use App\Domain\Source\Source;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class SampleDataMapperRegistry implements SampleDataMapperRegistryInterface
{
    private array $dataMappers;

    public function __construct(
        #[TaggedIterator('app.sample_data_mapper')] iterable $dataMappers
    ) {
        foreach ($dataMappers as $dataMapper) {
            foreach ($dataMapper as $item) {
                foreach ($item as $subitem) {
                    $this->dataMappers[] = $subitem;
                }
            }
        }
    }

    public function mapToTs(Source $source, array $sample): \SplFixedArray
    {
        $dataMapper = $this->getMapperForSource($source);

        return $dataMapper->mapToTs(source: $source, sample: $sample);
    }

    public function supports(Source $source): bool
    {
        $mapper = $this->getMapperForSource($source);

        return $mapper->supports($source);
    }

    public function getTimeStampFieldName(Source $source): string
    {
        $mapper = $this->getMapperForSource($source);

        return $mapper->getTimeStampFieldName($source);
    }

    public function getColumns(Source $source): array
    {
        $mapper = $this->getMapperForSource($source);

        return $mapper->getColumns($source);
    }

    public function getMapperForSource(Source $source): ?SampleDataMapper
    {
        /** @var SampleDataMapper $dataMapper */
        foreach ($this->dataMappers as $dataMapper) {
            if ($dataMapper->supports($source)) {
                return $dataMapper;
            }
        }
        throw new \RuntimeException(sprintf('Missing data mapper for %s of type %s in '.__METHOD__, $source->getExchange()->name, $source->getSourceType()->name));
    }
}
