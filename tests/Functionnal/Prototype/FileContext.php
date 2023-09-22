<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Prototype;

use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSample;
use App\Domain\Store\Adapter\RedisTimeSeries\TimeSeriesInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleAggregationRule;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleDuplicatePolicyList;
use App\Infra\Source\File\BybitFileOutputTransformer;
use Behat\Behat\Context\Context;
use Doctrine\Common\Collections\ArrayCollection;
use League\Csv\Writer;
use Symfony\Component\Finder\Finder;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertGreaterThan;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertTrue;

final class FileContext implements Context
{
    private \SplFileInfo $fileInfo;
    private string $downloadDir;
    private array $filenames = [];
    private \SplFileInfo $csvFileInfo;
    private array $fileResult = [];
    private ?array $resultSet = null;
    private TimeSeriesInterface $datastore;

    public function __construct(
        TimeSeriesInterface $datastore,
        string $downloadDir
    ) {
        $this->datastore = $datastore;
        $this->downloadDir = $downloadDir;
    }

    /**
     * @Given I receive a :arg1 event and retrieve the file information
     */
    public function iReceiveAEventAndRetrieveTheFileInformation($arg1)
    {
        $rootDir = getcwd().DIRECTORY_SEPARATOR.'tests/Functionnal/data/';
        $destDir = getcwd().DIRECTORY_SEPARATOR.'var/data/download/';

        $fileData = json_decode(file_get_contents($rootDir.'2023-09-18.json'), true);
        $fileName = $fileData['files'][0]['name'];
        $finder = new Finder();

        $finder = $finder->in($rootDir)->files();
        foreach ($finder->getIterator() as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getFilename() === $fileName) {
                $fileInfo = $file;
            }
        }
        copy($fileInfo->getPathname(), $destDir.$fileName);

        $finder = $finder->in($destDir)->files();
        $targetInfo = null;
        foreach ($finder->getIterator() as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getFilename() === $fileName) {
                $targetInfo = $file;
            }
        }

        $this->fileInfo = $targetInfo;
    }

    /**
     * @Then I can list the files
     */
    public function iCanListTheFiles()
    {
        $finder = new Finder();
        $rootDir = getcwd();
        $finder = $finder->in($rootDir.DIRECTORY_SEPARATOR.$this->downloadDir)->files();
        $this->filenames = array_filter(iterator_to_array($finder->getIterator()), function (\SplFileInfo $info) {
            return 'file' === $info->getType();
        });

        assertGreaterThan(0, count($this->filenames));
    }

    /**
     * @Then I download the first file of the list in the :arg1 directory
     */
    public function iDownloadTheFirstFileOfTheListInTheDirectory($arg1)
    {
        $file = array_shift($this->filenames);
        $this->fileInfo = $file;
    }

    /**
     * @Then I store the data into Redis under the key :arg1
     */
    public function iStoreTheDataIntoRedisUnderTheKey($arg1)
    {
        $this->datastore->set(key: $arg1, value: json_encode($this->fileResult), expiration: 600);
    }

    /**
     * @Then I aggregate the tick data by one second step
     */
    public function iAggregateTheTickDataByOneSecondStep()
    {
        $rawData = $this->resultSet;
        $timeStamps = array_map(function (array $raw) {
            return (float) $raw['timestamp'];
        }, $rawData);

        $startTs = floor(min($timeStamps));
        $endTs = ceil(max($timeStamps));
        $ohlcv = [];
        for ($t = $startTs; $t <= $endTs; ++$t) {
            $ticks = array_filter($rawData, function (array $data) use ($t) {
                $ts = (float) $data['timestamp'];

                return $ts >= $t && $ts < $t + 1;
            });
            if (1 === count($ticks)) {
                $tick = array_pop($ticks);
                $ohlcv[] = TickAggregator::getOne($tick, $t);
            }
            if (count($ticks) > 1) {
                $priceOhlcv = TickAggregator::aggregate($ticks, $t);
                $ohlcv[] = $priceOhlcv->toArray();
            }
        }
        $this->fileResult = $ohlcv;
    }

    //    private TimeSeriesInterface $datastore;
    //    private array $open = [];
    //    private array $high = [];
    //    private array $low = [];
    //    private array $close = [];
    //
    //    private array $csvRecords = [];

    //    /**
    //     * @Then I want to parse the file to a simple array with time as key and price as value
    //     */
    //    public function iWantToParseTheFileToASimpleArrayWithTimeAsKeyAndPriceAsValue()
    //    {
    //        $data = new ArrayCollection($this->resultSet);
    //        $result = BybitFileOutputTransformer::transform($data);
    //        assertIsArray($result);
    //        $sample = $result[0];
    //        assertIsArray($sample);
    //        assertArrayHasKey('price', $sample);
    //        assertArrayHasKey('time', $sample);
    //        $this->fileResult = $result;
    //    }
    //
    //    /**
    //     * @Given I query the data with key :arg1
    //     */
    //    public function iQueryTheDataWithKey($arg1)
    //    {
    //        $sample = $this->datastore->get($arg1);
    //        $arrayOfPrices = json_decode($sample, true);
    //        $times = array_map(function (array $price) {
    //            return (int) floor($price['time']);
    //        }, $arrayOfPrices);
    //
    //        foreach ($times as $time) {
    //            $found = array_filter($times, function ($value) use ($time) {
    //                return $value >= $time && $value < $time + 1;
    //            });
    //            if (count($found) > 1) {
    //                $ticks = array_filter($arrayOfPrices, function ($dataPoints) use ($time) {
    //                    return $dataPoints['time'] >= $time && $dataPoints['time'] < $time + 1;
    //                });
    //                $price = TickAggregator::aggregate($ticks, $time);
    //            }
    //        }
    //    }
    //
    //    /**
    //     * @Given I have a test-sample with key :arg1
    //     */
    //    public function iHaveATestSampleWithKey($arg1)
    //    {
    //        $json = json_decode($this->datastore->get($arg1), true);
    //        assertGreaterThan(1000, count($json));
    //    }

    //    /**
    //     * @Then I create a TimeSeries in redis with key :arg1 and expiration :arg2
    //     */
    //    public function iCreateATimeseriesInRedisWithKeyAndExpiration($arg1, $arg2)
    //    {
    //        $this->datastore->create($arg1, $arg2 * 1000);
    //    }
    //
    //    /**
    //     * @When I add the first datapoint of the sample :arg1 to the ts named :arg2
    //     */
    //    public function iAddTheFirstDatapointOfTheSampleToTheTsNamed($arg1, $arg2)
    //    {
    //        $sample = $this->datastore->get($arg1);
    //        $json = json_decode($sample, true);
    //        assertGreaterThan(1000, count($json));
    //        $first = $json[0];
    //        $this->datastore->add(new RawSample($arg2, $first['price'], (int) $first['time']));
    //    }
    //
    //    /**
    //     * @Given I add :arg3 more datapoints of the sample :arg1 to the ts named :arg2
    //     */
    //    public function iAddMoreDatapointsOfTheSampleToTheTsNamed($arg1, $arg2, $arg3)
    //    {
    //        $sample = $this->datastore->get($arg1);
    //        $json = json_decode($sample, true);
    //        assertGreaterThan(1000, count($json));
    //        for ($i = 1; $i <= (int) $arg3; ++$i) {
    //            $data = $json[$i];
    //            $sample = new RawSample($arg2, $data['price'], (int) $data['time']);
    //            $this->datastore->add(rawSample: $sample, duplicatePolicy: SampleDuplicatePolicyList::LAST->value);
    //        }
    //    }
    //
    //    /**
    //     * @Then the ts named :arg1 should have :arg2 datapoints
    //     */
    //    public function theTsNamedShouldHaveDatapoints($arg1, $arg2)
    //    {
    //        $info = $this->datastore->info($arg1);
    //        assertEquals($info->getTotalSamples(), (int) $arg2);
    //    }
    //
    //    /**
    //     * @Given I add all the datapoints of the sample :arg1 to the ts named :arg2
    //     */
    //    public function iAddAllTheDatapointsOfTheSampleToTheTsNamed($arg1, $arg2)
    //    {
    //        $sample = $this->datastore->get($arg1);
    //        $json = json_decode($sample, true);
    //        $startTs = time() * 1000;
    //        $endTs = 0;
    //
    //        foreach ($json as $data) {
    //            if ((int) $data['time'] <= $startTs) {
    //                $startTs = (int) $data['time'];
    //            }
    //            if ((int) $data['time'] >= $endTs) {
    //                $endTs = (int) $data['time'];
    //            }
    //            $sample = new RawSample($arg2, $data['price'], (int) $data['time']);
    //
    //            $this->datastore->add(rawSample: $sample, duplicatePolicy: SampleDuplicatePolicyList::LAST->value);
    //        }
    //
    //        $this->startTs = $startTs;
    //        $this->endTs = $endTs;
    //    }
    //
    //    /**
    //     * @Then the ts named :arg1 should have more than :arg2 datapoints
    //     */
    //    public function theTsNamedShouldHaveMoreThanDatapoints($arg1, $arg2)
    //    {
    //        $info = $this->datastore->info($arg1);
    //        assertGreaterThan((int) $arg2, (int) $info->getTotalSamples());
    //    }
    //
    //    /**
    //     * @Then I can request the entire range of the ts named :arg1 with an aggregation per minute
    //     */
    //    public function iCanRequestTheEntireRangeOfTheTsNamedWithAnAggregationPerMinute($arg1)
    //    {
    //        $now = time() * 1000;
    //        $this->low = $this->datastore->range(
    //            key: $arg1,
    //            from: $this->startTs - 10000,
    //            to: $now,
    //            count: null,
    //            rule: new SampleAggregationRule(
    //                SampleAggregationRule::AGG_MIN,
    //                1000 * 60,
    //            ),
    //        );
    //
    //        $this->high = $this->datastore->range(
    //            key: $arg1,
    //            from: $this->startTs - 10000,
    //            to: $now,
    //            count: null,
    //            rule: new SampleAggregationRule(
    //                SampleAggregationRule::AGG_MAX,
    //                1000 * 60
    //            )
    //        );
    //
    //        $this->open = $this->datastore->range(
    //            key: $arg1,
    //            from: $this->startTs - 10000,
    //            to: $now,
    //            count: null,
    //            rule: new SampleAggregationRule(
    //                SampleAggregationRule::AGG_FIRST,
    //                1000 * 60
    //            )
    //        );
    //
    //        $this->close = $this->datastore->range(
    //            key: $arg1,
    //            from: $this->startTs - 10000,
    //            to: $now,
    //            count: null,
    //            rule: new SampleAggregationRule(
    //                SampleAggregationRule::AGG_LAST,
    //                1000 * 60
    //            )
    //        );
    //    }
    //
    //    /**
    //     * @Then all the aggregations should have the same length
    //     */
    //    public function allTheAggregationsShouldHaveTheSameLength()
    //    {
    //        $openCount = count($this->open);
    //        $highCount = count($this->high);
    //        $lowCount = count($this->low);
    //        $closeCount = count($this->close);
    //        assertTrue($openCount === $highCount);
    //        assertTrue($highCount === $lowCount);
    //        assertTrue($lowCount === $closeCount);
    //    }
    //
    //    /**
    //     * @Then I should be able to create a new OHLC ts :arg1
    //     */
    //    public function iShouldBeAbleToCreateANewOhlcTs($arg1)
    //    {
    //        $count = count($this->close);
    //
    //        $records = [];
    //        for ($i = 0; $i < $count; ++$i) {
    //            $openData = $this->open[$i];
    //            $highData = $this->high[$i];
    //            $lowData = $this->low[$i];
    //            $closeData = $this->close[$i];
    //            $time = $openData->getTsms() / 1000;
    //            $dt = new \DateTime();
    //            $dt->setTimestamp($time);
    //
    //            $record['Date'] = $dt->format('Y-m-d H:i:s');
    //            $record['open'] = $openData->getValue();
    //            $record['high'] = $highData->getValue();
    //            $record['low'] = $lowData->getValue();
    //            $record['close'] = $closeData->getValue();
    //            $records[] = $record;
    //        }
    //
    //        $this->csvRecords = $records;
    //    }
    //
    //    /**
    //     * @Then I can export the OHLC to a csv file :arg1 in the directory :arg2
    //     */
    //    public function iCanExportTheOhlcToACsvFileInTheDirectory($arg1, $arg2)
    //    {
    //        $header = ['Date', 'Open', 'High', 'Low', 'Close'];
    //        $records = $this->csvRecords;
    //        // load the CSV document from a string
    //        $csv = Writer::createFromString();
    //        $csv->insertOne($header);
    //        $csv->insertAll($records);
    //
    //        file_put_contents($arg2.DIRECTORY_SEPARATOR.$arg1, $csv->toString()); // returns the CSV document as a string
    //    }
}
