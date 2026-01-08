<?php

declare(strict_types=1);

namespace Tests\Modules\Crypto\DX;

use App\Modules\Crypto\DX\CryptoContextFactory;
use App\Modules\Crypto\HKDF\HKDFService;
use App\Modules\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use App\Modules\Crypto\KeyRotation\KeyRotationService;
use App\Modules\Crypto\KeyRotation\KeyStatusEnum;
use App\Modules\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use App\Modules\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use App\Modules\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use App\Modules\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use PHPUnit\Framework\TestCase;

final class CryptoContextFactoryTest extends TestCase
{
    public function testFactoryCreatesOpaqueCryptoService(): void
    {
        // Arrange real, minimal collaborators (no mocks)
        $keys = [
            new CryptoKeyDTO(
                'k1',
                random_bytes(32),
                KeyStatusEnum::ACTIVE,
                new \DateTimeImmutable()
            ),
        ];

        $keyRotation = new KeyRotationService(
            new InMemoryKeyProvider($keys),
            new StrictSingleActiveKeyPolicy()
        );

        $hkdf = new HKDFService();

        $registry = new ReversibleCryptoAlgorithmRegistry();
        $registry->register(new Aes256GcmAlgorithm());

        $factory = new CryptoContextFactory(
            $keyRotation,
            $hkdf,
            $registry
        );

        // Act
        $result = $factory->create('test:v1');

        // Assert (DX-level only)
        $this->assertIsObject($result);
    }
}

