<?php

declare(strict_types=1);

namespace App\App\Event;

use App\Domain\Coin;
use App\Domain\Source\Exchange;

class FileDownloadedEvent
{
    private string $exchangeName;
    private array $coin;
    private array $files;

    public function __construct(
        Exchange $exchange,
        Coin $coin,
        array $downloadedFiles
    ) {
        $this->exchangeName = $exchange->value;
        $this->coin = $coin->toArray();
        $this->files = $downloadedFiles;
    }

    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }

    public function getCoin(): array
    {
        return $this->coin;
    }

    public function getFiles(): array
    {
        return $this->files;
    }
}
