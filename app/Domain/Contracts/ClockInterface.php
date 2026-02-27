<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use DateTimeImmutable;


/**
 * Contrato del dominio para obtener la fecha/hora actual.
 * Solo el dominio puede depender de este contrato.
 */
interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
