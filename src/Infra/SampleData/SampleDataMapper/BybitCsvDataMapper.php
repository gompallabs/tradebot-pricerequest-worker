<?php

declare(strict_types=1);

namespace App\Infra\SampleData\SampleDataMapper;

use App\Domain\SampleData\SampleDataMapper;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;

class BybitCsvDataMapper implements SampleDataMapper
{
    public function mapToTs(Source $source, array $sample): \SplFixedArray
    {
        $result = new \SplFixedArray(5);
        if ($this->supports($source)) {
            $size = (float) $sample['size'];
            $result[0] = (float) $sample['timestamp'];
            $result[1] = $size;
            $result[2] = (float) $sample['price'];
            $result[3] = 'Buy' === $sample['side'] ? $size : 0;
            $result[4] = 'Sell' === $sample['side'] ? $size : 0;
        }

        return $result;
    }

    public function getTimeStampFieldName(Source $source): string
    {
        if ($this->supports($source)) {
            return 'timestamp';
        }

        return '';
    }

    public function supports(Source $source): bool
    {
        return 'Bybit' === $source->getExchange()->name && SourceType::File === $source->getSourceType();
    }

    public function getColumns(Source $source): array
    {
        if ($this->supports($source)) {
            return [
                'timestamp',
                'size',
                'price',
                'buyVolume',
                'sellVolume',
            ];
        }

        return [];
    }
}
