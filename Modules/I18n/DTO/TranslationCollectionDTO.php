<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

use IteratorAggregate;
use ArrayIterator;

/**
 * @phpstan-type TranslationList list<TranslationDTO>
 * @implements IteratorAggregate<int, TranslationDTO>
 */
final readonly class TranslationCollectionDTO implements IteratorAggregate
{
    /**
     * @param list<TranslationDTO> $items
     */
    public function __construct(
        public array $items,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return ArrayIterator<int, TranslationDTO>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
