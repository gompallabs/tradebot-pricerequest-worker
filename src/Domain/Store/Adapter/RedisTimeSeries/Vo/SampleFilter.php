<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries\Vo;

use App\Domain\Store\Adapter\RedisTimeSeries\Exception\InvalidFilterOperationException;

/**
 * Filter on SampleLabels.
 */
final class SampleFilter
{
    public const OP_EQUALS = 0;
    public const OP_NOT_EQUALS = 1;
    public const OP_EXISTS = 2;
    public const OP_NOT_EXISTS = 3;
    public const OP_IN = 4;
    public const OP_NOT_IN = 5;

    private const OPERATIONS = [
        self::OP_EQUALS,
        self::OP_NOT_EQUALS,
        self::OP_EXISTS,
        self::OP_NOT_EXISTS,
        self::OP_IN,
        self::OP_NOT_IN,
    ];

    /**
     * @var array<array{string, int, string|array|null}>
     */
    private array $filters = [];

    public function __construct(string $label, string $value)
    {
        $this->filters[] = [$label, self::OP_EQUALS, $value];
    }

    public function add(string $label, int $operation, $value = null): self
    {
        if (!in_array($operation, self::OPERATIONS, true)) {
            throw new InvalidFilterOperationException('Operation is not valid');
        }

        if (!is_string($value) && in_array($operation, [self::OP_EQUALS, self::OP_NOT_EQUALS], true)) {
            throw new InvalidFilterOperationException('The provided operation requires the value to be string');
        }

        if (null !== $value && in_array($operation, [self::OP_EXISTS, self::OP_NOT_EXISTS], true)) {
            throw new InvalidFilterOperationException('The provided operation requires the value to be null');
        }

        if (!is_array($value) && in_array($operation, [self::OP_IN, self::OP_NOT_IN], true)) {
            throw new InvalidFilterOperationException('The provided operation requires the value to be an array');
        }

        $this->filters[] = [$label, $operation, $value];

        return $this;
    }

    public function toRedisParams(): array
    {
        $params = [];
        foreach ($this->filters as $filter) {
            switch ($filter[1]) {
                case self::OP_EQUALS:
                    assert(is_string($filter[2]));
                    $params[] = $filter[0].'='.$filter[2];
                    break;
                case self::OP_NOT_EQUALS:
                    assert(is_string($filter[2]));
                    $params[] = $filter[0].'!='.$filter[2];
                    break;
                case self::OP_EXISTS:
                    $params[] = $filter[0].'=';
                    break;
                case self::OP_NOT_EXISTS:
                    $params[] = $filter[0].'!=';
                    break;
                case self::OP_IN:
                    assert(is_array($filter[2]));
                    $params[] = $filter[0].'=('.implode(',', $filter[2]).')';
                    break;
                case self::OP_NOT_IN:
                    assert(is_array($filter[2]));
                    $params[] = $filter[0].'!=('.implode(',', $filter[2]).')';
                    break;
            }
        }

        return $params;
    }
}
