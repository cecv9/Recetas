<?php
declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class IngredientesRecipe
{
    private const MAX_LENGTH = 3000;

    private string $ingredientes;

    public function __construct(string $ingredientes)
    {
        $normalizados = str_replace(["\r\n", "\r"], "\n", $ingredientes);
        $normalizados = trim($normalizados);

        if ($normalizados === '') {
            throw new InvalidArgumentException("Los ingredientes no pueden estar vacios");
        }

        if (!mb_check_encoding($normalizados, 'UTF-8')) {
            throw new InvalidArgumentException("Los ingredientes deben ser UTF-8 valido");
        }

        if ($this->containsHtmlTags($normalizados)) {
            throw new InvalidArgumentException("Los ingredientes no pueden contener etiquetas HTML");
        }

        if ($this->containsControlChars($normalizados)) {
            throw new InvalidArgumentException("Los ingredientes no pueden contener caracteres de control");
        }

        if (mb_strlen($normalizados, 'UTF-8') > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Los ingredientes no pueden tener mas de " . self::MAX_LENGTH . " caracteres"
            );
        }

        $compacto = preg_replace('/\s+/u', ' ', $normalizados);
        if ($compacto === null) {
            throw new InvalidArgumentException("Los ingredientes tienen un formato invalido");
        }

        if (mb_strtolower($compacto, 'UTF-8') === 'sin ingredientes') {
            throw new InvalidArgumentException("Los ingredientes no pueden ser 'sin ingredientes'");
        }

        $this->ingredientes = $normalizados;
    }

    public function equals(self $otro): bool
    {
        return $this->ingredientes === $otro->ingredientes;
    }

    public function __toString(): string
    {
        return $this->ingredientes;
    }

    private function containsHtmlTags(string $value): bool
    {
        return (bool) preg_match('/<\s*\/?\s*[a-z0-9]+[^>]*>/iu', $value);
    }

    private function containsControlChars(string $value): bool
    {
        return (bool) preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value);
    }
}
