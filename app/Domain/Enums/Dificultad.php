<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum Dificultad: string
{
    case Facil = 'facil';
    case Media = 'media';
    case Dificil = 'dificil';
}