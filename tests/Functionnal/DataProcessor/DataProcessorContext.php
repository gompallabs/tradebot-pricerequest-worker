<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\DataProcessor;

use App\App\Event\ApiRequestEvent;
use App\App\Event\FileDownloadedEvent;
use App\App\Handler\ApiRequestEventHandler;
use App\Domain\Coin;
use App\Domain\SampleData\SampleDataProcessor;
use App\Domain\Source\Exchange;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;
use App\Domain\TickData;
use App\Infra\Source\File\BybitCsvFileParser;
use App\Infra\Source\File\BybitFileDecompressor;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Symfony\Component\Finder\Finder;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertTrue;

class DataProcessorContext implements Context
{
    private ?array $eventData = null;
    private string $dataDirectory;
    private ?\SplFileInfo $csvFileInfo = null; // uncompressed file

    private array $resultSet = []; // csv rows
    private SampleDataProcessor $dataProcessor;

    private ?string $eventName = null;

    private array $tickData = [];

    private ?TickData $processingResult = null;

    private ?Source $source = null;
    private ApiRequestEventHandler $apiRequestEventHandler;

    private ?TickData $result = null;

    public function __construct(
        SampleDataProcessor $dataProcessor,
        ApiRequestEventHandler $apiRequestEventHandler
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->apiRequestEventHandler = $apiRequestEventHandler;
    }

    /**
     * @Given an ApiRequestEvent event is received on :arg1 queue
     */
    public function anApirequesteventEventIsReceivedOnQueue($arg1)
    {
        $finder = new Finder();
        $finder->in(getcwd().DIRECTORY_SEPARATOR.'tests/Functionnal/DataProcessor')->files();
        foreach ($finder->getIterator() as $file) {
            if ('api.json' === $file->getFilename()) {
                $fileInfo = $file;
            }
        }
        $contents = file_get_contents($fileInfo->getRealPath());
        $this->eventData = json_decode($contents, true);
        $this->eventName = ApiRequestEvent::class;
    }

    /**
     * @Given a FileDownloadedEvent event is received on :arg1 queue
     */
    public function aFiledownloadedeventEventIsReceivedOnQueue($arg1)
    {
        $finder = new Finder();
        $finder->in(getcwd().DIRECTORY_SEPARATOR.'tests/Functionnal/DataProcessor')->files();
        foreach ($finder->getIterator() as $file) {
            if ('file.json' === $file->getFilename()) {
                $fileInfo = $file;
            }
        }
        $contents = file_get_contents($fileInfo->getRealPath());
        $this->eventData = json_decode($contents, true);
        $this->eventName = FileDownloadedEvent::class;
    }

    /**
     * @Then I copy the file with a :arg1 extension
     */
    public function iCopyTheFileWithAExtension($arg1)
    {
        $fileName = $this->eventData['files'][0]['name'];
        $directory = getcwd().DIRECTORY_SEPARATOR.'tests/Functionnal/Data';
        $this->dataDirectory = $directory;
        copy($directory.DIRECTORY_SEPARATOR.$fileName, $directory.DIRECTORY_SEPARATOR.$fileName.'.backup');
    }

    /**
     * @Then I restore the backup file to the original file
     */
    public function iRestoreTheBackupFileToTheOriginalFile()
    {
        $fileName = $this->eventData['files'][0]['name'];
        $directory = $this->dataDirectory;
        copy($directory.DIRECTORY_SEPARATOR.$fileName.'.backup', $directory.DIRECTORY_SEPARATOR.$fileName);
    }

    /**
     * @Then I parse the csv file
     */
    public function iParseTheCsvFile()
    {
        $iterator = BybitCsvFileParser::parse($this->csvFileInfo);
        $this->resultSet = iterator_to_array($iterator);
    }

    /**
     * @Then I decompress the file
     */
    public function iDecompressTheFile()
    {
        $fileName = $this->eventData['files'][0]['name'];
        $finder = new Finder();
        $finder = $finder->in($this->dataDirectory)->files();
        $fileInfo = null;
        foreach ($finder->getIterator() as $file) {
            if ($file->getFilename() === $fileName) {
                $fileInfo = $file;
            }
        }

        $decompressor = new BybitFileDecompressor($fileInfo);
        $csvFileInfo = $decompressor->execute();
        assertInstanceOf(\SplFileInfo::class, $csvFileInfo);
        $this->csvFileInfo = $csvFileInfo;
    }

    /**
     * @Then the event should contain a Coin data array
     */
    public function theEventShouldContainACoinDataArray()
    {
        assertArrayHasKey('coin', $this->eventData);
    }

    /**
     * @Then the event should contain a Source data array
     */
    public function theEventShouldContainASourceDataArray()
    {
        assertArrayHasKey('source', $this->eventData);
    }

    /**
     * @Then the event should contain a data array
     */
    public function theEventShouldContainADataArray()
    {
        assertArrayHasKey('data', $this->eventData);
    }

    /**
     * @Then I can transform it with the data processor to an OHLCV series
     */
    public function iCanTransformItWithTheDataProcessorToAnOhlcvSeries()
    {
        if (ApiRequestEvent::class === $this->eventName) {
            $data = $this->eventData['data'];
        }

        if (FileDownloadedEvent::class === $this->eventName) {
            $data = $this->resultSet;
        }

        $sourceData = $this->eventData['source'];
        $exchange = Exchange::tryFrom($sourceData['exchange']['name']);
        $source = new Source(exchange: $exchange, sourceType: SourceType::File);
        $this->process($source, $data);
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
     * @Given I check the columns
     */
    public function iCheckTheColumns()
    {
        $sample = $this->tickData[0];
        $result = $this->dataProcessor->analyzeSample(sample: $sample, source: $this->source);
        assertTrue($result);
    }

    /**
     * @Then I can use the data processor to obtain an OHLCV series from the tick data
     */
    public function iCanUseTheDataProcessorToObtainAnOhlcvSeriesFromTheTickData()
    {
        $source = new Source(Exchange::Bybit, SourceType::File);
        $data = $this->tickData;
        $this->process($source, $data);
    }

    private function process(Source $source, array $data): void
    {
        $this->dataProcessor->analyzeSample(sample: end($data), source: $source);
        $result = $this->dataProcessor->process(source: $source, payload: $data);
        assertInstanceOf(TickData::class, $result);
        $this->processingResult = $result;
    }

    /**
     * @Then the tickData should contain :arg1 PriceOhlcv objects
     */
    public function theTickdataShouldContainPriceohlcvObjects($arg1)
    {
        assertEquals((int) $arg1, $this->processingResult->count());
    }

    /**
     * @Then the PriceOhlcv object nb :arg1 should have the following properties:
     */
    public function thePriceohlcvObjectNbShouldHaveTheFollowingProperties($arg1, PyStringNode $string)
    {
        $tickData = $this->processingResult;
        $iterator = $tickData->getIterator();
        $ohlcv = $iterator->offsetGet((int) $arg1);
        $candle = $ohlcv->toArray();
        $spec = json_decode(implode('', $string->getStrings()), true);
        foreach ($spec as $key => $value) {
            assertArrayHasKey($key, $candle);
            $result = $candle[$key];
            if (is_float($result)) {
                $result = round($result, 3);
            }
            assertEquals($value, $result);
        }
    }

    /**
     * @Given I handle the event with the ApiRequestEventHandler
     */
    public function iHandleTheEventWithTheApirequesteventhandler()
    {
        $data = $this->eventData;
        $sourceData = $data['source'];
        $coinData = $data['coin'];
        $data = $data['data'];
        $event = new ApiRequestEvent(
            source: new Source(
                exchange: Exchange::tryFrom($sourceData['exchange']['name']),
                sourceType: SourceType::RestApi
            ),
            coin: new Coin(
                ticker: $coinData['ticker'],
                category: $coinData['category']
            ),
            data: $data
        );
        $handler = $this->apiRequestEventHandler;
        $this->result = $handler->__invoke($event);
    }

    /**
     * @Then the handler should return an OHLCV series
     */
    public function theHandlerShouldReturnAnOhlcvSeries()
    {
        assertInstanceOf(TickData::class, $this->result);
        $this->processingResult = $this->result;
    }
}
