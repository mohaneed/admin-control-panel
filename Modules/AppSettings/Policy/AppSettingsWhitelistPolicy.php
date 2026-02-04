<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 20:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AppSettings\Policy;

use Maatify\AppSettings\Exception\InvalidAppSettingException;

/**
 * Class: AppSettingsWhitelistPolicy
 *
 * Defines which setting groups and keys are allowed
 * to exist inside the AppSettings module.
 *
 * IMPORTANT:
 * - Any group/key not explicitly allowed is rejected
 * - This prevents chaos, typos, and silent config drift
 */
final class AppSettingsWhitelistPolicy
{
    /**
     * Allowed groups and their allowed keys.
     *
     * NOTE:
     * - Keys list may contain '*' to allow any key under the group
     * - Be explicit unless there is a strong reason not to
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED = [
        'social' => [
            'email',
            'facebook',
            'twitter',
            'instagram',
            'linkedin',
            'youtube',
            'whatsapp',
        ],

        'apps' => [
            'android',
            'ios',
            'huawei',
            'android_agent',
            'ios_agent',
            'huawei_agent',
        ],

        'legal' => [
            'about_us',
            'privacy_policy',
            'returns_refunds_policy',
        ],

        'meta'          => [
            'dev_name',
            'dev_url',
        ],

        // Feature flags are intentionally flexible
        'feature_flags' => ['*'],

        // System-level settings (VERY sensitive)
        'system'        => [
            'base_url',
            'environment',
            'timezone',
        ],
    ];

    /**
     * Validate that a group and key are allowed.
     *
     * @throws InvalidAppSettingException
     */
    public static function assertAllowed(string $group, string $key): void
    {
        $group = self::normalize($group);
        $key = self::normalize($key);

        if (! isset(self::ALLOWED[$group])) {
            throw new InvalidAppSettingException(
                sprintf('Setting group "%s" is not allowed', $group)
            );
        }

        $allowedKeys = self::ALLOWED[$group];

        if (in_array('*', $allowedKeys, true)) {
            return;
        }

        if (! in_array($key, $allowedKeys, true)) {
            throw new InvalidAppSettingException(
                sprintf('Setting key "%s.%s" is not allowed', $group, $key)
            );
        }
    }

    /**
     * Normalize input to a canonical format.
     *
     * Rules:
     * - lowercase
     * - trim spaces
     */
    private static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }
}
