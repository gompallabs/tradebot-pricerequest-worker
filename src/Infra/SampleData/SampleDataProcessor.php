<?php

declare(strict_types=1);

namespace App\Infra\SampleData;

use App\Domain\SampleData\SampleDataMapper;
use App\Domain\SampleData\SampleDataMapperRegistry as SampleDataMapperRegistryInterface;
use App\Domain\SampleData\SampleDataProcessor as DataProcessorInterface;
use App\Domain\Source\Source;
use App\Domain\TickData;
use App\Domain\TimeFormat;
use App\Domain\TimeFormatTools;

class SampleDataProcessor implements DataProcessorInterface
{
    private ?SampleDataMapper $dataMapper;

    private ?TimeFormat $timeFormat;

    public function __construct(
        private readonly SampleCheckerRegistry $sampleCheckerRegistry,
        private readonly SampleDataMapperRegistryInterface $dataMapperRegistry
    ) {
    }

    public function analyzeSample(array $sample, Source $source): bool
    {
        // throws if check fails
        $this->sampleCheckerRegistry->check($source, $sample);
        $this->dataMapper = $this->dataMapperRegistry->getMapperForSource($source);

        // time format
        $tsKey = $this->dataMapper->getTimeStampFieldName($source);
        $this->timeFormat = TimeFormatTools::guessTimeStampFormat($sample[$tsKey]);

        return true;
    }

    public function process(Source $source, array $payload, bool $splitData = false): TickData
    {
        $transformer = new SampleDataTransformer(
            dataMapper: $this->dataMapper,
            timeFormat: $this->timeFormat
        );

        return $transformer->fromPayload(source: $source, payload: $payload);
    }
}
