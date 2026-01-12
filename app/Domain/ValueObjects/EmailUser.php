<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class EmailUser
{
    private const MAX_LENGTH = 255;

    private string $emailUser;

    public function __construct(string $emailUser)
    {
        $emailUser = trim($emailUser);

        if ($emailUser === '') {
            throw new InvalidArgumentException('El email no puede estar vacio');
        }

        if(strtolower($emailUser) !== $emailUser) {
            throw new InvalidArgumentException('El email debe estar en minusculas');
        }

        if (!filter_var($emailUser, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El email no tiene un formato valido');
        }

        if (mb_strlen($emailUser, 'UTF-8') > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                'El email no puede tener mas de ' . self::MAX_LENGTH . ' caracteres'
            );
        }

        $this->emailUser = $emailUser;
    }


    public function equals(self $cambioEmailUser) : bool
    {
        return $this->emailUser === $cambioEmailUser->emailUser;

    }

    public function value(): string
    {
        return $this->emailUser;
    }
}
