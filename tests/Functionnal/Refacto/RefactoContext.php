<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Refacto;

use App\Domain\SampleData\SampleDataProcessor;
use App\Domain\Source\Exchange;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;
use App\Domain\TickData;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertTrue;

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
     * @Given I extracted the data from :arg1 exchange and from a csv :arg2 source
     */
    public function iExtractedTheDataFromExchangeAndFromACsvSource($arg1, $arg2)
    {
        $exchange = 'Bybit' === $arg1 ? Exchange::Bybit : null;
        $sourceType = null;
        if ('file' === $arg2) {
            $sourceType = SourceType::File;
        }
        if ('api' === $arg2) {
            $sourceType = SourceType::RestApi;
        }

        assertInstanceOf(Exchange::class, $exchange);
        assertInstanceOf(SourceType::class, $sourceType);

        $this->source = new Source($exchange, $sourceType);
    }

    /**
     * @Given I have the tickData:
     */
    public function iHaveTheTickdata(TableNode $table)
    {
        foreach ($table->getIterator() as $raw) {
            $this->tickData[] = $raw;
        }
    }

    /**
     * @Given I check the columns
     */
    public function iCheckTheColumns()
    {
        $sample = $this->tickData[0];
        $result = $this->dataProcessor->analyzeSample(sample: $sample, source: $this->source);
        assertTrue($result);
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

    /**
     * @Then the tickData should contain :arg1 PriceOhlcv objects
     */
    public function theTickdataShouldContainPriceohlcvObjects($arg1)
    {
        assertEquals((int) $arg1, $this->processedtickData->count());
    }
}
