<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Reader;

use Maatify\AdminKernel\Domain\DTO\TranslationValueList\TranslationValueListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface TranslationValueQueryReaderInterface
{
    public function queryTranslationValues(
        int $languageId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): TranslationValueListResponseDTO;
}
