<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Hashing\BcryptHasher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ImportedBcryptPasswordTest extends TestCase
{
    public function test_imported_bcrypt_authenticates_without_changing_the_stored_hash(): void
    {
        $password = 'Mot-de-passe-éà-🔒';
        $native = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
        $imported = '$2b$'.substr($native, 4);
        $user = new User;
        $user->setRawAttributes(['password' => $imported], true);
        $provider = new EloquentUserProvider(new BcryptHasher(['verify' => true]), User::class);

        $this->assertTrue(password_verify($password, $imported));
        $this->assertTrue($provider->validateCredentials($user, ['password' => $password]));
        $this->assertFalse($provider->validateCredentials($user, ['password' => 'incorrect']));
        $this->assertSame($imported, $user->getRawOriginal('password'));
        $this->assertFalse($user->isDirty());
    }

    public function test_native_bcrypt_still_authenticates(): void
    {
        $hash = password_hash('test-password', PASSWORD_BCRYPT, ['cost' => 4]);
        $user = new User;
        $user->setRawAttributes(['password' => $hash]);
        $provider = new EloquentUserProvider(new BcryptHasher(['verify' => true]), User::class);

        $this->assertSame($hash, $user->getAuthPassword());
        $this->assertTrue($provider->validateCredentials($user, ['password' => 'test-password']));
    }

    public function test_other_formats_are_not_rewritten_and_algorithm_verification_stays_enabled(): void
    {
        $user = new User;
        $user->setRawAttributes(['password' => '$2b$invalid']);
        $this->assertSame('$2b$invalid', $user->getAuthPassword());
        $provider = new EloquentUserProvider(new BcryptHasher(['verify' => true]), User::class);

        $this->expectException(RuntimeException::class);
        $provider->validateCredentials($user, ['password' => 'test-password']);
    }

    public function test_missing_password_is_rejected(): void
    {
        $provider = new EloquentUserProvider(new BcryptHasher(['verify' => true]), User::class);
        $this->assertFalse($provider->validateCredentials(new User, ['password' => 'test-password']));
    }
}
