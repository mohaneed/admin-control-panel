<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Service\PasswordService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PasswordServiceTest extends TestCase
{
    private array $peppers = [
        'v1' => 'secret-v1-must-be-long-enough-technically',
        'v2' => 'secret-v2-must-be-long-enough-technically'
    ];
    private string $activeId = 'v2';
    private PasswordService $service;

    protected function setUp(): void
    {
        $this->service = new PasswordService($this->peppers, $this->activeId);
    }

    public function testHashUsesActivePepper(): void
    {
        $password = 'secret123';
        $result = $this->service->hash($password);

        $this->assertSame($this->activeId, $result['pepper_id']);
        $this->assertNotEmpty($result['hash']);
    }

    public function testVerifySuccessWithCorrectPepperId(): void
    {
        $password = 'secret123';
        $result = $this->service->hash($password);

        $this->assertTrue($this->service->verify($password, $result['hash'], $result['pepper_id']));
    }

    public function testVerifyFailsWithWrongPassword(): void
    {
        $password = 'secret123';
        $result = $this->service->hash($password);

        $this->assertFalse($this->service->verify('wrong', $result['hash'], $result['pepper_id']));
    }

    public function testVerifyFailsWithUnknownPepperId(): void
    {
        $password = 'secret123';
        $result = $this->service->hash($password);

        // Pass a pepper ID that doesn't exist in the map
        $this->assertFalse($this->service->verify($password, $result['hash'], 'unknown-id'));
    }

    public function testVerifyWorksWithInactiveButValidPepper(): void
    {
        // Create a service where v1 is active
        $serviceV1 = new PasswordService($this->peppers, 'v1');
        $resultV1 = $serviceV1->hash('secret123');

        // Verify using the main service (where v2 is active)
        // It should still verify because v1 is in the map
        $this->assertTrue($this->service->verify('secret123', $resultV1['hash'], 'v1'));
    }

    public function testNeedsRehash(): void
    {
        // Active is v2
        $this->assertTrue($this->service->needsRehash('v1'));
        $this->assertFalse($this->service->needsRehash('v2'));
    }

    public function testConstructorThrowsOnEmptyPeppers(): void
    {
        $this->expectException(RuntimeException::class);
        new PasswordService([], 'v1');
    }

    public function testConstructorThrowsOnMissingActiveId(): void
    {
        $this->expectException(RuntimeException::class);
        new PasswordService(['v1' => 's'], 'v2');
    }
}
