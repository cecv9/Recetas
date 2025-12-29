<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Enums\Dificultad;
use App\Domain\ValueObjects\IngredientesRecipe;
use App\Domain\ValueObjects\TiempoPreparacionRecipe;
use App\Domain\ValueObjects\TituloRecipe;

use DateTimeImmutable;

class Recipe {

    private  ?int $id;

    private  int $autorId;

    private  TituloRecipe $titulo;

    private  int $tipoCocinaId;

    private  IngredientesRecipe $ingredientes;

    private TiempoPreparacionRecipe  $tiempoPreparacion;

    private Dificultad  $dificultad;

    private bool $activo ;

    private ?DateTimeImmutable $fechaCreacion;



    public function __construct(int $autorId,TituloRecipe $titulo,int $tipoCocinaId, IngredientesRecipe  $ingredientes,TiempoPreparacionRecipe $tiempoPreparacion, Dificultad $dificultad,?DateTimeImmutable $fechaCreacion=null,?int $id=null,bool $activo = true )
    {
       
     
        if($autorId <=0){
            throw new \InvalidArgumentException("El ID del autor debe ser mayor a cero");
        }
        if($tipoCocinaId <=0){
            throw new \InvalidArgumentException("El ID del tipo de cocina debe ser mayor a cero");
        }
        if($id !== null && $id <=0){
            throw new \InvalidArgumentException("El ID de la receta debe ser mayor a cero");
        }
       
        if($fechaCreacion !== null && $fechaCreacion > new DateTimeImmutable()){
            throw new \InvalidArgumentException("La fecha de creacion no puede ser futura");
        }
    

        $this->id=$id;

        $this->autorId=$autorId;

        $this->titulo=$titulo;
        
        $this->tipoCocinaId=$tipoCocinaId;

        $this->ingredientes=$ingredientes;

        $this->tiempoPreparacion=$tiempoPreparacion;

        $this->dificultad=$dificultad;

        $this->activo=$activo;

        $this->fechaCreacion = $fechaCreacion ?? new DateTimeImmutable();

        
    }

   // Getters (sin cambios)
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAutorId(): int
    {
        return $this->autorId;
    }

    public function getTitulo(): TituloRecipe
    {
        return $this->titulo;
    }

    public function getTipoCocinaId(): int
    {
        return $this->tipoCocinaId;
    }

    public function getIngredientes(): IngredientesRecipe
    {
        return $this->ingredientes;
    }

    public function getTiempoPreparacion(): TiempoPreparacionRecipe
    {
        return $this->tiempoPreparacion;
    }

    public function getDificultad(): Dificultad
    {
        return $this->dificultad;
    }

  

   public function getFechaCreacion(): ?DateTimeImmutable
{
    return $this->fechaCreacion;
}


    // Métodos de dominio (reemplazan setters con validación)
    public function cambiarTitulo(TituloRecipe $nuevotitulo): void
    {   
         if($this->titulo->equals($nuevotitulo)) return;
    
         $this->titulo = $nuevotitulo;
         
    }


    public function cambiarTipoCocinaId(int $tipoCocinaId): void
    {
        if ($tipoCocinaId <= 0) {
            throw new \InvalidArgumentException("El ID del tipo de cocina debe ser mayor a cero");
        }
        $this->tipoCocinaId = $tipoCocinaId;
    }

  public function cambiarIngredientes(IngredientesRecipe $nuevoIngredientes): void
    {
        if($this->ingredientes->equals($nuevoIngredientes)) return;
        $this->ingredientes = $nuevoIngredientes;
    }

    public function cambiarTiempoPreparacion(TiempoPreparacionRecipe $nuevoTiempoPreparacion): void
    
    {   if($this->tiempoPreparacion->equals($nuevoTiempoPreparacion)) return;
        $this->tiempoPreparacion = $nuevoTiempoPreparacion;
    }

    // Versión corregida
    public function cambiarDificultad(Dificultad $dificultad): void
    {
    $this->dificultad = $dificultad;  // El tipo ya lo protege
    }

    // Métodos de dominio para activo (ya estaban bien)
   public function isActivo(): bool
    {
        return $this->activo;
    }

    public function activar(): void
    {
        $this->activo = true;
    }

    public function desactivar(): void
    {
        $this->activo = false;
    }

}

