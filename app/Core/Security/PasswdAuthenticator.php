<?php

namespace App\Core\Security;

use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\SimpleIdentity;

readonly class PasswdAuthenticator implements Authenticator
{
    public function __construct(private string $passwdFile)
    {
    }

    public function authenticate(string $username, string $password): SimpleIdentity
    {
        $userData = $this->getUserData($username);
        if (!$userData) {
            throw new AuthenticationException('Uživatel neexistuje.', self::IdentityNotFound);
        }

        [$storedUsername, $storedHash] = $userData;
        if (!password_verify($password, $storedHash)) {
            throw new AuthenticationException('Neplatné heslo.', self::InvalidCredential);
        }

        return new SimpleIdentity($username);
    }

    /**
     * @throws AuthenticationException
     */
    private function getUserData(string $username): ?array
    {
        if (!file_exists($this->passwdFile) || !is_readable($this->passwdFile)) {
            throw new AuthenticationException('Soubor s uživateli není dostupný.');
        }

        $file = fopen($this->passwdFile, 'r');
        while (($line = fgets($file)) !== false) {
            $parts = explode(':', trim($line));
            if (count($parts) < 2) {
                continue;
            }

            if ($parts[0] === $username) {
                fclose($file);
                return $parts;
            }
        }

        fclose($file);
        return null;
    }
}
