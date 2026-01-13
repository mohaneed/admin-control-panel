<?php

declare(strict_types=1);

namespace App\Domain\Service;

use RuntimeException;

class PasswordService
{
    /**
     * @param array<string, string> $peppers Map of pepper_id => secret
     * @param string $activePepperId The ID of the pepper to use for new hashes
     */
    public function __construct(
        private readonly array $peppers,
        private readonly string $activePepperId
    ) {
        if (empty($this->peppers)) {
            throw new RuntimeException('Password Peppers must be configured.');
        }
        if (!isset($this->peppers[$this->activePepperId])) {
            throw new RuntimeException("Active Pepper ID '{$this->activePepperId}' not found in configured peppers.");
        }
        foreach ($this->peppers as $id => $secret) {
            if (strlen($secret) < 32) {
                throw new RuntimeException("Pepper secret for ID '{$id}' is too short (min 32 chars).");
            }
        }
    }

    /**
     * Hashes a password using the currently active pepper.
     *
     * @return array{hash: string, pepper_id: string}
     */
    public function hash(string $plain): array
    {
        $pepper = $this->peppers[$this->activePepperId];
        $peppered = hash_hmac('sha256', $plain, $pepper);
        $hash = password_hash($peppered, PASSWORD_ARGON2ID);

        return [
            'hash' => $hash,
            'pepper_id' => $this->activePepperId
        ];
    }

    /**
     * Verifies a password against a stored hash using the specific pepper ID.
     *
     * @param string $plain The plaintext password
     * @param string $hash The stored hash
     * @param string $pepperId The ID of the pepper used to create the hash
     * @return bool True if valid
     */
    public function verify(string $plain, string $hash, string $pepperId): bool
    {
        if (!isset($this->peppers[$pepperId])) {
            // Fail closed if pepper ID is unknown (cannot verify)
            return false;
        }

        $pepper = $this->peppers[$pepperId];
        $peppered = hash_hmac('sha256', $plain, $pepper);

        return password_verify($peppered, $hash);
    }

    public function needsRehash(string $pepperId): bool
    {
        return $pepperId !== $this->activePepperId;
    }
}
