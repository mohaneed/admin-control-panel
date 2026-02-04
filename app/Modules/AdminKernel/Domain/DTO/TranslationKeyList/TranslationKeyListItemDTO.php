<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\TranslationKeyList;

use JsonSerializable;

/**
 * @phpstan-type TranslationKeyListItemArray array{
 *   id: int,
 *   key_name: string,
 *   description: string|null,
 *   created_at: string,
 *   updated_at: string|null
 * }
 */
final readonly class TranslationKeyListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $keyName,
        public ?string $description,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @return TranslationKeyListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'key_name' => $this->keyName,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
