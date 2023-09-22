<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Prototype;

use App\Domain\Source\Api\ClientBuilder;
use App\Domain\TimeFormat;
use App\Infra\Source\Api\BybitApiClientResponseTransformer;
use App\Infra\Source\Api\BybitApiReponseValidator;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertGreaterThanOrEqual;
use function PHPUnit\Framework\assertInstanceOf;

class RecentTradesContext
{
    public function __construct(
        ClientBuilder $clientBuilder,
        BybitApiReponseValidator $apiReponseValidator,
        BybitApiClientResponseTransformer $transformer,
    ) {
        $this->clientBuilder = $clientBuilder;
        $this->apiReponseValidator = $apiReponseValidator;
        $this->transformer = $transformer;
    }

    /**
     * @Given I aggregate the tick data with a :arg1 second step
     */
    public function iAggregateTheTickDataWithASecondStep($arg1)
    {
        // move to aggregator to have split series output
        $this->splitSeries = null;
        $tickData = $this->responseContent;
        $transformer = $this->tickDataTransformer;
        $tickData = new TickData(iterator_to_array($tickData), TimeFormat::DotMilliseconds);
        $transformer->checkColumns($tickData);
        $transformed = $transformer->chronoSort($tickData);
        $tsScale = new TsScale((int) $transformed['startTs'], (int) $transformed['endTs'], 100);
        $this->splitSeries = $transformer->splitResample($transformed['tickData'], $tsScale, $this->tsKey);
        assertInstanceOf(SplitSeries::class, $this->splitSeries);
    }

    /**
     * @Given the aggregate should contain all the open, high, low, close, buyVolume, sellVolume series
     */
    public function theAggregateShouldContainAllTheOpenHighLowCloseBuyvolumeSellvolumeSeries()
    {
        $aggregate = $this->splitSeries;
        assertInstanceOf(SampleSeries::class, $aggregate->getOpen());
        assertInstanceOf(SampleSeries::class, $aggregate->getHigh());
        assertInstanceOf(SampleSeries::class, $aggregate->getLow());
        assertInstanceOf(SampleSeries::class, $aggregate->getClose());
        assertInstanceOf(SampleSeries::class, $aggregate->getBuyVolume());
        assertInstanceOf(SampleSeries::class, $aggregate->getSellVolume());
    }

    /**
     * @Given each split serie should have the same number of elements
     */
    public function eachSplitSerieShouldHaveTheSameNumberOfElements()
    {
        $aggregate = $this->splitSeries;
        $count = $aggregate->getOpen()->count();
        assertEquals($count, $aggregate->getClose()->count());
        assertEquals($count, $aggregate->getHigh()->count());
        assertEquals($count, $aggregate->getLow()->count());
        assertEquals($count, $aggregate->getBuyVolume()->count());
        assertEquals($count, $aggregate->getSellVolume()->count());
        assertGreaterThanOrEqual(1, $count);
    }
}
