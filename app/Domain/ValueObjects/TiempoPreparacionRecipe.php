<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class TiempoPreparacionRecipe
{
    private const MINIMO_MINUTOS = 15;
    private const MAXIMO_MINUTOS = 300;

    private int $minutos;

    public function __construct(int $minutos)
    {
        if ($minutos < self::MINIMO_MINUTOS || $minutos > self::MAXIMO_MINUTOS) {
            throw new InvalidArgumentException(
                sprintf(
                    'El tiempo debe estar entre %d y %d minutos',
                    self::MINIMO_MINUTOS,
                    self::MAXIMO_MINUTOS
                )
            );
        }

        $this->minutos = $minutos;
    }

    /**
     * Crea desde formato "H:MM" (para formularios)
     */
    public static function desdeFormato(string $formato): self
    {
        $formato = trim($formato);

        if (!preg_match('/^(\d+):([0-5]\d)$/', $formato, $m)) {
            throw new InvalidArgumentException(
                'El formato debe ser H:MM (ejemplo: 1:30)'
            );
        }

        $horas = (int) $m[1];
        $min = (int) $m[2];
        $totalMinutos = ($horas * 60) + $min;

        return new self($totalMinutos);
    }

    public function minutos(): int
    {
        return $this->minutos;
    }

    public function enHoras(): float
    {
        return $this->minutos / 60;
    }

    public function formato(): string
    {
        $horas = intdiv($this->minutos, 60);
        $min = $this->minutos % 60;

        return sprintf('%d:%02d', $horas, $min);
    }

    public function __toString(): string
    {
        return $this->formato();
    }

    public function equals(self $otro): bool
    {
        return $this->minutos === $otro->minutos;
    }
}
