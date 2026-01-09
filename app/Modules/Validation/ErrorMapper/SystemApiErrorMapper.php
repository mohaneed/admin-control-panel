<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Validation\ErrorMapper;

use App\Modules\Validation\Contracts\SystemErrorMapperInterface;
use App\Modules\Validation\DTO\ApiErrorResponseDTO;
use App\Modules\Validation\Enum\AuthErrorCodeEnum;
use App\Modules\Validation\Enum\HttpStatusCodeEnum;
use App\Modules\Validation\Enum\ValidationErrorCodeEnum;

final class SystemApiErrorMapper implements SystemErrorMapperInterface
{
    /**
     * @param ValidationErrorCodeEnum  $errors
     * @param array<string, list<ValidationErrorCodeEnum>> $errors
     */
    public function mapValidationErrors(array $errors): ApiErrorResponseDTO
    {
        $mapped = [];

        foreach ($errors as $field => $codes) {
            foreach ($codes as $code) {
                $mapped[$field][] = $code->value;
            }
        }

        return new ApiErrorResponseDTO(
            status: HttpStatusCodeEnum::BAD_REQUEST->value,
            code: 'INPUT_INVALID',
            errors: $mapped
        );
    }

    public function mapAuthError(AuthErrorCodeEnum $errorCode): ApiErrorResponseDTO
    {
        return new ApiErrorResponseDTO(
            status: HttpStatusCodeEnum::FORBIDDEN->value,
            code: $errorCode->value
        );
    }
}

