<?php

namespace Maatify\I18n\DTO;

use IteratorAggregate;
use ArrayIterator;

/**
 * @phpstan-type TranslationKeyList list<TranslationKeyDTO>
 * @implements IteratorAggregate<int, TranslationKeyDTO>
 */
final readonly class TranslationKeyCollectionDTO implements IteratorAggregate
{
    /**
     * @param list<TranslationKeyDTO> $items
     */
    public function __construct(
        public array $items,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return ArrayIterator<int, TranslationKeyDTO>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
