<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Refacto;

use App\Domain\SampleData\SampleDataProcessor;
use App\Domain\Source\Source;
use App\Domain\TickData;
use Behat\Behat\Context\Context;

use function PHPUnit\Framework\assertInstanceOf;

class RefactoContext implements Context
{
    private array $tickData = [];

    private ?Source $source = null;
    private SampleDataProcessor $dataProcessor;
    private ?TickData $processedtickData = null;

    public function __construct(SampleDataProcessor $dataProcessor)
    {
        $this->dataProcessor = $dataProcessor;
    }

    /**
     * @Given I use the DataProcessor
     */
    public function iUseTheDataprocessor()
    {
        $dataProcessor = $this->dataProcessor;
        $tickData = $dataProcessor->process($this->source, $this->tickData);
        assertInstanceOf(TickData::class, $tickData);
        $this->processedtickData = $tickData;
    }
}
