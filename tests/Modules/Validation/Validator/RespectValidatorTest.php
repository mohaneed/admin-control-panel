<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 02:37
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Validation\Validator;
use App\Modules\Validation\Schemas\LoginSchema;
use App\Modules\Validation\Validator\RespectValidator;
use PHPUnit\Framework\TestCase;

final class RespectValidatorTest extends TestCase
{
    public function testDelegatesToSchema(): void
    {
        $validator = new RespectValidator();
        $schema = new LoginSchema();

        $result = $validator->validate($schema, [
            'email'    => 'bad',
            'password' => 'bad',
        ]);

        self::assertFalse($result->isValid());
    }
}
