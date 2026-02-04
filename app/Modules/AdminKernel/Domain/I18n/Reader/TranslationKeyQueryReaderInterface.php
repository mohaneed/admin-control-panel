<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Reader;

use Maatify\AdminKernel\Domain\DTO\TranslationKeyList\TranslationKeyListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface TranslationKeyQueryReaderInterface
{
    public function queryTranslationKeys(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): TranslationKeyListResponseDTO;
}
