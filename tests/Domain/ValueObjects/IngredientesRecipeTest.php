<?php
declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use App\Domain\ValueObjects\IngredientesRecipe;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class IngredientesRecipeTest extends TestCase
{
    public function test_permite_saltos_de_linea(): void
    {
        $vo = new IngredientesRecipe("harina\nazucar");

        $this->assertSame("harina\nazucar", (string) $vo);
    }

    public function test_permite_ampersand(): void
    {
        $vo = new IngredientesRecipe("sal & pimienta");

        $this->assertSame("sal & pimienta", (string) $vo);
    }

    public function test_rechaza_html_tags(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IngredientesRecipe("<b>azucar</b>");
    }

    public function test_rechaza_sin_ingredientes_con_saltos_de_linea(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IngredientesRecipe("sin\n ingredientes");
    }
}
