<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\DataProcessor;

use App\App\Event\ApiRequestEvent;
use App\App\Event\FileDownloadedEvent;
use App\Domain\SampleData\SampleDataProcessor;
use App\Domain\Source\Exchange;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;
use App\Domain\TickData;
use App\Infra\Source\File\BybitCsvFileParser;
use App\Infra\Source\File\BybitFileDecompressor;
use Behat\Behat\Context\Context;
use Symfony\Component\Finder\Finder;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertInstanceOf;

class DataProcessorContext implements Context
{
    private ?array $eventData = null;
    private string $dataDirectory;
    private ?\SplFileInfo $csvFileInfo = null; // uncompressed file

    private array $resultSet = []; // csv rows
    private SampleDataProcessor $dataProcessor;

    private ?string $eventName = null;

    public function __construct(SampleDataProcessor $dataProcessor)
    {
        $this->dataProcessor = $dataProcessor;
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
        $this->dataProcessor->analyzeSample(sample: end($data), source: $source);
        $result = $this->dataProcessor->process(source: $source, payload: $data);
        assertInstanceOf(TickData::class, $result);
    }
}
