<?php

declare(strict_types=1);

namespace App\App\Handler;

use App\App\Event\FileDownloadedEvent;
use App\Domain\SampleData\SampleDataProcessor;
use App\Infra\Source\File\BybitCsvFileParser;
use App\Infra\Source\File\BybitFileDecompressor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FileDownloadedEventHandler
{
    private string $downloadDir;
    private SampleDataProcessor $dataProcessor;

    public function __construct(string $downloadDir, SampleDataProcessor $dataProcessor)
    {
        $this->downloadDir = $downloadDir;
        $this->dataProcessor = $dataProcessor;
    }

    public function __invoke(FileDownloadedEvent $event): void
    {
        $downloadedFiles = $event->getFiles();
        $downloadedFile = array_shift($downloadedFiles);

        $finder = new Finder();
        $finder = $finder->in($this->downloadDir)->files();
        $fileInfo = null;
        foreach ($finder->getIterator() as $file) {
            if ($file->getFilename() === $downloadedFile['name']) {
                $fileInfo = $file;
            }
        }

        if (null === $fileInfo) {
            throw new \RuntimeException(sprintf('File not found: %s', $downloadedFile['name']));
        }

        // decompress file
        $decompressor = new BybitFileDecompressor($fileInfo);
        $csvFileInfo = $decompressor->execute();

        $iterator = BybitCsvFileParser::parse($csvFileInfo);
        $sample = $iterator->current();
        $this->dataProcessor->analyzeSample(sample: $sample, source: $event->getSource());
    }
}
