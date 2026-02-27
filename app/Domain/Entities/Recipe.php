<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Enums\Dificultad;
use App\Domain\ValueObjects\IngredientesRecipe;
use App\Domain\ValueObjects\TiempoPreparacionRecipe;
use App\Domain\ValueObjects\TituloRecipe;
use App\Domain\Contracts\ClockInterface;

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

    private readonly ClockInterface $clock;

    public function __construct(
        ClockInterface $clock,        // CAMBIO #1: Clock es OBLIGATORIO y va PRIMERO
                                       // Sin valor por defecto = se DEBE pasar siempre
        int $autorId,
        TituloRecipe $titulo,
        int $tipoCocinaId, 
        IngredientesRecipe  $ingredientes,
        TiempoPreparacionRecipe $tiempoPreparacion, 
        Dificultad $dificultad,
        ?int $id=null,                // CAMBIO #2: Eliminado el parámetro $fechaCreacion
                                       // El backend SIEMPRE genera la fecha
        bool $activo = true 
    )
    {
        $this->clock = $clock;        // CAMBIO #3: Asignamos el clock a la propiedad
                                       // Sin esto, $this->clock->now() daría error

        $now = $this->clock->now();   // CAMBIO #4: Obtenemos fecha del clock INYECTADO
                                       // NO usamos new DateTimeImmutable() directamente
                                       // NO usamos funciones externas como now() de Symfony

        // Validaciones de IDs
        if($autorId <=0){
            throw new \InvalidArgumentException("El ID del autor debe ser mayor a cero");
        }
        if($tipoCocinaId <=0){
            throw new \InvalidArgumentException("El ID del tipo de cocina debe ser mayor a cero");
        }
        if($id !== null && $id <=0){
            throw new \InvalidArgumentException("El ID de la receta debe ser mayor a cero");
        }

        // CAMBIO #5: ELIMINADA la validación de "fecha futura"
        // Ya NO aceptamos fecha externa, asi que no hay nada que validar
        // El problema de coordinación de tiempos DESAPARECE

        $this->id=$id;

        $this->autorId=$autorId;

        $this->titulo=$titulo;
        
        $this->tipoCocinaId=$tipoCocinaId;

        $this->ingredientes=$ingredientes;

        $this->tiempoPreparacion=$tiempoPreparacion;

        $this->dificultad=$dificultad;

        $this->activo=$activo;

        $this->fechaCreacion = $now;  // CAMBIO #6: ASIGNACIÓN SIMPLE
                                       // El backend es el JEFE de la fecha
                                       // Una sola línea, sin "??" ni condiciones
                                       // $now ya tiene el valor del clock
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
    
    {   
        if($this->tiempoPreparacion->equals($nuevoTiempoPreparacion)) return;
        $this->tiempoPreparacion = $nuevoTiempoPreparacion;
    }

    public function cambiarDificultad(Dificultad $dificultad): void
    {
    $this->dificultad = $dificultad;
    }


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
