<?php

declare(strict_types=1);

namespace App\Infrastructure\Clock;

use App\Domain\Contracts\ClockInterface;
use DateTimeImmutable;

/**
 * Implementación concreta del contrato ClockInterface utilizando la fecha/hora del sistema.
 * Esta clase es parte de la infraestructura y puede depender de detalles específicos del entorno.
 */
class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

}
