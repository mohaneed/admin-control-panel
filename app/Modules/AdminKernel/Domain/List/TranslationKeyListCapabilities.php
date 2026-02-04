<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

final class TranslationKeyListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
        // 🔍 Global search (free text)
            supportsGlobalSearch : true,
            searchableColumns    : [
                'key_name',
                'description',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns    : [
                'key_name' => 'key_name',
                'description' => 'description',
            ],

            supportsDateFilter   : false,
            dateColumn           : null
        );
    }
}

