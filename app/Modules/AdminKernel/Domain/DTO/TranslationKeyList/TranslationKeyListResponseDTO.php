<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\TranslationKeyList;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use JsonSerializable;

/**
 * @phpstan-type TranslationKeyListResponseArray array{
 *   data: TranslationKeyListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final readonly class TranslationKeyListResponseDTO implements JsonSerializable
{
    /**
     * @param TranslationKeyListItemDTO[] $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {}

    /**
     * @return TranslationKeyListResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
