<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap\Container;
use App\Kernel\DTO\AdminRuntimeConfigDTO;
use Dotenv\Dotenv;

use App\Domain\Contracts\AdminTotpSecretStoreInterface;
use App\Infrastructure\Repository\AdminRepository;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\Service\PasswordService;
use App\Domain\Ownership\SystemOwnershipRepositoryInterface;
use Ramsey\Uuid\Uuid;

/*
|--------------------------------------------------------------------------
| 1️⃣ Load ENV (HOST responsibility)
|--------------------------------------------------------------------------
*/
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

/*
|--------------------------------------------------------------------------
| 2️⃣ Build Runtime Config DTO
|--------------------------------------------------------------------------
*/
$runtimeConfig = AdminRuntimeConfigDTO::fromArray($_ENV);

/*
|--------------------------------------------------------------------------
| 3️⃣ Create Container (NO env loading inside)
|--------------------------------------------------------------------------
*/
$container = Container::create($runtimeConfig);

/*
|--------------------------------------------------------------------------
| 4️⃣ Safety check: admins already exist
|--------------------------------------------------------------------------
*/
$pdo = $container->get(PDO::class);
assert($pdo instanceof PDO);

$stmt = $pdo->query("SELECT COUNT(*) FROM admins");
assert($stmt !== false);

if ((int)$stmt->fetchColumn() > 0) {
    echo "Bootstrap disabled: Admins already exist.\n";
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 5️⃣ Inputs
|--------------------------------------------------------------------------
*/
echo "Bootstrap First Admin\n";

$email = readline("Email: ");
$password = readline("Password: ");

if ($email === '' || $password === '') {
    echo "Email and Password required.\n";
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 6️⃣ TOTP Setup
|--------------------------------------------------------------------------
*/
$totpService = $container->get(TotpServiceInterface::class);
assert($totpService instanceof TotpServiceInterface);

$secret = $totpService->generateSecret();

echo "TOTP Secret: {$secret}\n";
echo "Set it in your authenticator app.\n";

$code = readline("Enter OTP Code: ");

if ($code === false || !$totpService->verify($secret, $code)) {
    echo "Invalid OTP. Aborting.\n";
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 7️⃣ Transaction
|--------------------------------------------------------------------------
*/
try {
    $pdo->beginTransaction();

    // 1. Admin
    $adminRepo = $container->get(AdminRepository::class);
    assert($adminRepo instanceof AdminRepository);

    $adminId = $adminRepo->createFirstAdmin();

    // 2. Email
    $emailRepo = $container->get(AdminEmailRepository::class);
    assert($emailRepo instanceof AdminEmailRepository);

    $cryptoService = $container->get(
        App\Application\Crypto\AdminIdentifierCryptoServiceInterface::class
    );

    assert($cryptoService instanceof App\Application\Crypto\AdminIdentifierCryptoServiceInterface);

    $blindIndex = $cryptoService->deriveEmailBlindIndex($email);
    $encryptedPayload = $cryptoService->encryptEmail($email);

    $emailId = $emailRepo->addEmail($adminId, $blindIndex, $encryptedPayload);
    $emailRepo->markVerified(
        $emailId,
        (new DateTimeImmutable())->format('Y-m-d H:i:s')
    );

    // 3. Password
    $passRepo = $container->get(AdminPasswordRepositoryInterface::class);
    $passwordService = $container->get(PasswordService::class);

    assert($passRepo instanceof AdminPasswordRepositoryInterface);
    assert($passwordService instanceof PasswordService);

    $hashResult = $passwordService->hash($password);
    $passRepo->savePassword(
        $adminId,
        $hashResult['hash'],
        $hashResult['pepper_id'],
        false
    );

    // 4. TOTP Secret (encrypted store)
    $totpStore = $container->get(AdminTotpSecretStoreInterface::class);
    assert($totpStore instanceof AdminTotpSecretStoreInterface);

    $totpStore->store($adminId, $secret);

    // 5. System Ownership
    $ownershipRepo = $container->get(SystemOwnershipRepositoryInterface::class);
    assert($ownershipRepo instanceof SystemOwnershipRepositoryInterface);

    $ownershipRepo->assignOwner($adminId);

    $pdo->commit();

    echo "Admin created successfully. ID: {$adminId}\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "Error: {$e->getMessage()}\n";
    exit(1);
}
