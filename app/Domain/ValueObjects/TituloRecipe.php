<?php
declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class TituloRecipe {


private string $titulo; 

public function __construct(string $tituloRecipe)

{

    $tituloRecipe=trim($tituloRecipe);

   if ($this->containsHtmlTags($tituloRecipe)) {
    throw new InvalidArgumentException("El titulo no puede contener etiquetas HTML");
   }

    if($tituloRecipe===''){
        throw new InvalidArgumentException("El titulo no puede estar vacio");
    }
    if(mb_strlen($tituloRecipe, 'UTF-8') > 255){
        throw new InvalidArgumentException("El titulo no puede tener mas de 255 caracteres");
    }
    if(mb_strtolower($tituloRecipe, 'UTF-8') === 'sin titulo'){
        throw new InvalidArgumentException("El titulo no puede ser 'sin titulo'");
    }

  
    if (!mb_check_encoding($tituloRecipe, 'UTF-8')) {
    throw new InvalidArgumentException("El titulo debe ser UTF-8 valido");
    }

    $this->titulo=$tituloRecipe;

}

    public function equals(self $other): bool
    {
        return $this->titulo === $other->titulo;

    }

    public function __toString(): string
    {
        return $this->titulo;
    }   

    private function containsHtmlTags(string $value): bool

    {
    return (bool) preg_match('/<\s*\/?\s*[a-z0-9]+[^>]*>/iu', $value);
    }



}