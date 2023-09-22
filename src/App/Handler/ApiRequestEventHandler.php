<?php

declare(strict_types=1);

namespace App\App\Handler;

use App\App\Event\ApiRequestEvent;
use App\Domain\SampleData\SampleDataProcessor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ApiRequestEventHandler
{
    private SampleDataProcessor $dataProcessor;

    public function __construct(SampleDataProcessor $dataProcessor)
    {
        $this->dataProcessor = $dataProcessor;
    }

    public function __invoke(ApiRequestEvent $event)
    {
        $source = $event->getSource();
        $data = $event->getData();
        $sample = end($data);
        $this->dataProcessor->analyzeSample(sample: $sample, source: $source);

        return $this->dataProcessor->process(source: $source, payload: $data);
    }
}
