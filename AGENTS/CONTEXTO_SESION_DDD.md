# Contexto de Sesión: Aprendizaje DDD - Plataforma de Recetas

**Fecha:** Sesión de aprendizaje intensiva
**Alumno:** Carlos
**Objetivo:** Aprender DDD, arquitectura limpia y buenas prácticas en PHP

---

## 1. INFORMACIÓN DEL PROYECTO

### 1.1 Descripción
Plataforma donde los usuarios pueden:
- Registrarse y seleccionar preferencias culinarias
- Navegar entre distintas recetas de cocina
- Marcar recetas como favoritas
- Agregar recetas a lista de "Por probar"
- Ver gráficos de popularidad de recetas y tipos de cocina

### 1.2 Estructura de Base de Datos

```sql
-- Usuarios
CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL  -- soft delete
);

-- Tipos de cocina (catálogo)
CREATE TABLE tipos_cocina (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE
);

-- Recetas
CREATE TABLE recetas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,  -- autor
  titulo VARCHAR(255) NOT NULL,
  tipo_cocina_id INT UNSIGNED NOT NULL,
  ingredientes TEXT NOT NULL,
  tiempo_preparacion INT UNSIGNED NOT NULL,  -- minutos
  dificultad ENUM('facil','media','dificil') DEFAULT 'facil',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,  -- soft delete
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (tipo_cocina_id) REFERENCES tipos_cocina(id)
);

-- Favoritos (many-to-many)
CREATE TABLE favoritos (
  usuario_id INT UNSIGNED NOT NULL,
  receta_id INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, receta_id)
);

-- Por probar (many-to-many)
CREATE TABLE por_probar (
  usuario_id INT UNSIGNED NOT NULL,
  receta_id INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, receta_id)
);

-- Preferencias culinarias (many-to-many)
CREATE TABLE preferencias_culinarias (
  usuario_id INT UNSIGNED NOT NULL,
  tipo_cocina_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (usuario_id, tipo_cocina_id)
);
```

### 1.3 Arquitectura de Directorios (4 capas)

```
app/
├── Config/
├── Core/
│   ├── Http/
│   ├── Database/
│   ├── Logging/
│   ├── Security/
│   ├── Routing/
│   └── Container/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Forms/
├── Application/
│   ├── Services/
│   ├── DTO/
│   └── Exceptions/
├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Enums/
│   └── Contracts/
└── Infrastructure/
    ├── Persistence/
    │   └── Repository/
    └── Authorization/
```

---

## 2. CONCEPTOS DDD APRENDIDOS

### 2.1 Separación Domain / Infrastructure

**Principio fundamental:** El Domain es el corazón de la aplicación y NO debe depender de nada externo (ni BD, ni HTTP, ni frameworks).

**Analogía del Restaurante:**
```
🍳 COCINA (Domain)
   - Sabe QUÉ es una receta
   - Conoce las REGLAS de negocio
   - NO le importa cómo se guardan los datos

📋 GERENCIA (Application)  
   - Coordina el flujo
   - Orquesta, pero no cocina

🚪 RECEPCIÓN (Http/Controllers)
   - Recibe requests
   - Entrega responses
   
📦 BODEGA (Infrastructure)
   - Guarda los datos (BD)
   - El Domain NO entra aquí directamente
```

**Regla de dependencias:** Las flechas apuntan hacia adentro (hacia Domain).

```
HTTP → Application → Domain ← Infrastructure
```

### 2.2 Hidratación

**Definición:** Convertir datos "muertos" (filas de BD, arrays) en objetos "vivos" (con métodos y comportamiento).

**Proceso:**
```
MySQL (datos crudos)     Repository (traduce)      Domain (objetos)
─────────────────────    ─────────────────────     ─────────────────
deleted_at = NULL    →   "Si es NULL, activa"  →   activa = true
deleted_at = fecha   →   "Si tiene fecha"      →   activa = false
'facil' (string)     →   Dificultad::from()    →   Dificultad::Facil
90 (int)             →   new TiempoPrep(90)    →   TiempoPreparacion
```

### 2.3 Repository como Traductor Bidireccional

```
LECTURA (SELECT):    MySQL → Repository → Domain
ESCRITURA (INSERT):  Domain → Repository → MySQL
```

El Repository "habla ambos idiomas": SQL y Objetos de dominio.

### 2.4 Lenguaje de Negocio vs Lenguaje Técnico

| MySQL (técnico) | Domain (negocio) |
|-----------------|------------------|
| deleted_at | activa |
| usuario_id | autorId |
| created_at | fechaCreacion |
| 'facil' | Dificultad::Facil |

### 2.5 Relaciones Entre Aggregates

**Decisión:** Solo guardar IDs, no objetos completos.

```php
// ❌ Incorrecto - carga objeto completo
private User $autor;

// ✅ Correcto - solo el ID
private int $autorId;
```

**Razones:**
- Evita el problema N+1 (múltiples consultas)
- Respeta los límites del Aggregate
- El autor tiene datos sensibles (password) que Recipe no debe conocer

### 2.6 Escritura vs Lectura

```
ESCRITURA (Commands)              LECTURA (Queries)
════════════════════              ═════════════════
Crear/Editar/Eliminar             Listar/Buscar/Mostrar

Usa Entity + Repository           Puede usar SQL directo
Protege invariantes               No hay riesgo de corrupción
Validaciones estrictas            Solo proyecta datos

Si hay error → datos corruptos    Si hay error → molestia menor
```

---

## 3. CICLO DE VIDA DE UNA ENTITY

### 3.1 Dos Momentos

```
CREAR NUEVA                         RECONSTRUIR DESDE BD
════════════                        ════════════════════
Usuario llena formulario            Repository lee de MySQL
         ↓                                   ↓
new Recipe(...)                     new Recipe(..., id: 57)
         ↓                                   ↓
id = null (aún no existe)           id = 57 (ya existe)
activo = true (por defecto)         activo = lo que diga BD
fechaCreacion = ahora               fechaCreacion = de la BD
```

### 3.2 Parámetros del Constructor

**Orden correcto:**
1. Obligatorios primero
2. Opcionales al final (con valor por defecto)

```php
public function __construct(
    // OBLIGATORIOS (sin default)
    int $autorId,
    TituloRecipe $titulo,
    int $tipoCocinaId,
    IngredientesRecipe $ingredientes,
    TiempoPreparacionRecipe $tiempoPreparacion,
    Dificultad $dificultad,
    
    // OPCIONALES (con default, para hidratación)
    ?DateTimeImmutable $fechaCreacion = null,
    ?int $id = null,
    bool $activo = true
)
```

---

## 4. VALUE OBJECTS

### 4.1 Características

```
1. INMUTABLE      → Una vez creado, no cambia
2. POR VALOR      → Dos emails iguales son el mismo valor
3. SIN IDENTIDAD  → No tiene ID
4. AUTOVALIDANTE  → No puede existir en estado inválido
```

### 4.2 Cuándo Crear un ValueObject

**Preguntas guía:**
1. ¿El valor tiene REGLAS propias? (email, tiempo, etc.)
2. ¿Se REPITE en varias entidades?
3. ¿La validación es COMPLEJA?
4. ¿Tiene COMPORTAMIENTO propio?

### 4.3 Estructura Típica

```php
final readonly class MiValueObject
{
    private string $valor;

    public function __construct(string $valor)
    {
        // Validaciones
        if (empty($valor)) {
            throw new InvalidArgumentException("...");
        }
        
        $this->valor = $valor;
    }

    public function equals(self $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
```

### 4.4 Validaciones de Seguridad Obligatorias

```php
// 1. UTF-8
if (!mb_check_encoding($valor, 'UTF-8')) {
    throw new InvalidArgumentException("Debe ser UTF-8 válido");
}

// 2. HTML tags (previene XSS)
if ($this->containsHtmlTags($valor)) {
    throw new InvalidArgumentException("No puede contener HTML");
}

// 3. Caracteres de control
if ($this->containsControlChars($valor)) {
    throw new InvalidArgumentException("Caracteres inválidos");
}

// Métodos auxiliares
private function containsHtmlTags(string $value): bool
{
    return (bool) preg_match('/<\s*\/?\s*[a-z0-9]+[^>]*>/iu', $value);
}

private function containsControlChars(string $value): bool
{
    return (bool) preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value);
}
```

---

## 5. PROBLEMAS ENCONTRADOS Y SOLUCIONES

### 5.1 Enum No Existe (Crítico)

**Problema:**
```php
use App\Domain\Enums\Dificultad;  // Archivo no existía
```

**Efecto:** Fatal error, aplicación no arranca.

**Solución:** Crear el archivo `Dificultad.php`.

**Lección:** PSR-4 requiere que el archivo exista y coincida con el namespace.

---

### 5.2 DateTime Mutable (Alto)

**Problema:**
```php
private DateTime $fechaCreacion;

public function getFechaCreacion(): DateTime
{
    return $this->fechaCreacion;  // Expone objeto mutable
}
```

**Efecto:** Código externo puede modificar el estado interno:
```php
$fecha = $recipe->getFechaCreacion();
$fecha->modify('+10 years');  // ¡Modifica el interno!
```

**Solución:** Usar `DateTimeImmutable`:
```php
private DateTimeImmutable $fechaCreacion;

public function getFechaCreacion(): DateTimeImmutable
{
    return $this->fechaCreacion;
}
```

**Lección:** Nunca exponer objetos mutables desde una Entity.

---

### 5.3 $activo Forzado (Medio)

**Problema:**
```php
public function __construct(...)
{
    $this->activo = true;  // SIEMPRE true
}
```

**Efecto:** Al cargar receta inactiva de BD, se reactiva silenciosamente.

**Solución:** Agregar como parámetro:
```php
public function __construct(..., bool $activo = true)
{
    $this->activo = $activo;
}
```

**Lección:** Los valores que vienen de BD deben poder pasarse al constructor.

---

### 5.4 Validación HTML Inconsistente (Medio)

**Problema:**
```php
// TituloRecipe - muy restrictivo
htmlspecialchars($titulo) !== $titulo  // Rechaza "Pollo & Verduras"

// IngredientesRecipe - correcto
containsHtmlTags($valor)  // Solo rechaza <tags>
```

**Efecto:** Títulos válidos rechazados ("Pollo & Verduras").

**Solución:** Usar `containsHtmlTags()` en ambos.

**Lección:** Mantener consistencia en validaciones entre VOs similares.

---

### 5.5 Falta Validación UTF-8 (Bajo)

**Problema:** TituloRecipe no validaba UTF-8.

**Efecto:** Datos mal codificados podrían corromper BD.

**Solución:** Agregar:
```php
if (!mb_check_encoding($valor, 'UTF-8')) {
    throw new InvalidArgumentException("Debe ser UTF-8 válido");
}
```

---

### 5.6 Typo en Nombre de Archivo

**Problema:** `Difucultad.php` en vez de `Dificultad.php`

**Efecto:** Class not found.

**Lección:** PSR-4 requiere coincidencia exacta clase ↔ archivo.

---

## 6. CÓDIGO FINAL PRODUCIDO

### 6.1 Recipe.php (Entity)

```php
<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Enums\Dificultad;
use App\Domain\ValueObjects\IngredientesRecipe;
use App\Domain\ValueObjects\TiempoPreparacionRecipe;
use App\Domain\ValueObjects\TituloRecipe;
use DateTimeImmutable;

class Recipe
{
    private ?int $id;
    private int $autorId;
    private TituloRecipe $titulo;
    private int $tipoCocinaId;
    private IngredientesRecipe $ingredientes;
    private TiempoPreparacionRecipe $tiempoPreparacion;
    private Dificultad $dificultad;
    private bool $activo;
    private ?DateTimeImmutable $fechaCreacion;

    public function __construct(
        int $autorId,
        TituloRecipe $titulo,
        int $tipoCocinaId,
        IngredientesRecipe $ingredientes,
        TiempoPreparacionRecipe $tiempoPreparacion,
        Dificultad $dificultad,
        ?DateTimeImmutable $fechaCreacion = null,
        ?int $id = null,
        bool $activo = true
    ) {
        if ($autorId <= 0) {
            throw new \InvalidArgumentException("El ID del autor debe ser mayor a cero");
        }
        if ($tipoCocinaId <= 0) {
            throw new \InvalidArgumentException("El ID del tipo de cocina debe ser mayor a cero");
        }
        if ($id !== null && $id <= 0) {
            throw new \InvalidArgumentException("El ID de la receta debe ser mayor a cero");
        }
        if ($fechaCreacion !== null && $fechaCreacion > new DateTimeImmutable()) {
            throw new \InvalidArgumentException("La fecha de creacion no puede ser futura");
        }

        $this->id = $id;
        $this->autorId = $autorId;
        $this->titulo = $titulo;
        $this->tipoCocinaId = $tipoCocinaId;
        $this->ingredientes = $ingredientes;
        $this->tiempoPreparacion = $tiempoPreparacion;
        $this->dificultad = $dificultad;
        $this->activo = $activo;
        $this->fechaCreacion = $fechaCreacion ?? new DateTimeImmutable();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getAutorId(): int { return $this->autorId; }
    public function getTitulo(): TituloRecipe { return $this->titulo; }
    public function getTipoCocinaId(): int { return $this->tipoCocinaId; }
    public function getIngredientes(): IngredientesRecipe { return $this->ingredientes; }
    public function getTiempoPreparacion(): TiempoPreparacionRecipe { return $this->tiempoPreparacion; }
    public function getDificultad(): Dificultad { return $this->dificultad; }
    public function getFechaCreacion(): ?DateTimeImmutable { return $this->fechaCreacion; }
    public function isActivo(): bool { return $this->activo; }

    // Métodos de dominio
    public function cambiarTitulo(TituloRecipe $nuevoTitulo): void
    {
        if ($this->titulo->equals($nuevoTitulo)) return;
        $this->titulo = $nuevoTitulo;
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
        if ($this->ingredientes->equals($nuevoIngredientes)) return;
        $this->ingredientes = $nuevoIngredientes;
    }

    public function cambiarTiempoPreparacion(TiempoPreparacionRecipe $nuevoTiempo): void
    {
        if ($this->tiempoPreparacion->equals($nuevoTiempo)) return;
        $this->tiempoPreparacion = $nuevoTiempo;
    }

    public function cambiarDificultad(Dificultad $dificultad): void
    {
        $this->dificultad = $dificultad;
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
```

### 6.2 TituloRecipe.php (ValueObject)

```php
<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class TituloRecipe
{
    private string $titulo;

    public function __construct(string $tituloRecipe)
    {
        $tituloRecipe = trim($tituloRecipe);

        if (!mb_check_encoding($tituloRecipe, 'UTF-8')) {
            throw new InvalidArgumentException("El titulo debe ser UTF-8 valido");
        }

        if ($this->containsHtmlTags($tituloRecipe)) {
            throw new InvalidArgumentException("El titulo no puede contener etiquetas HTML");
        }

        if ($tituloRecipe === '') {
            throw new InvalidArgumentException("El titulo no puede estar vacio");
        }

        if (mb_strlen($tituloRecipe, 'UTF-8') > 255) {
            throw new InvalidArgumentException("El titulo no puede tener mas de 255 caracteres");
        }

        if (mb_strtolower($tituloRecipe, 'UTF-8') === 'sin titulo') {
            throw new InvalidArgumentException("El titulo no puede ser 'sin titulo'");
        }

        $this->titulo = $tituloRecipe;
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
```

### 6.3 IngredientesRecipe.php (ValueObject)

```php
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
```

### 6.4 TiempoPreparacionRecipe.php (ValueObject)

```php
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

    public static function desdeFormato(string $formato): self
    {
        $formato = trim($formato);

        if (!preg_match('/^(\d+):([0-5]\d)$/', $formato, $m)) {
            throw new InvalidArgumentException('El formato debe ser H:MM (ejemplo: 1:30)');
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
```

### 6.5 Dificultad.php (Enum)

```php
<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum Dificultad: string
{
    case Facil = 'facil';
    case Media = 'media';
    case Dificil = 'dificil';
}
```

---

## 7. ESTRUCTURA FINAL DEL DOMAIN

```
Domain/
├── Entities/
│   └── Recipe.php              ✅ Entity con validaciones
├── ValueObjects/
│   ├── TituloRecipe.php        ✅ Inmutable, autovalidante
│   ├── IngredientesRecipe.php  ✅ Con seguridad anti-XSS
│   └── TiempoPreparacionRecipe.php  ✅ Con método estático
├── Enums/
│   └── Dificultad.php          ✅ Enum tipado
└── Contracts/
    └── (pendiente: RecipeRepositoryInterface)
```

---

## 8. PRÓXIMOS PASOS

### 8.1 Inmediatos
1. Crear `RecipeRepositoryInterface` en Domain/Contracts
2. Crear `RecipeRepository` en Infrastructure/Persistence
3. Implementar hidratación completa

### 8.2 Posteriores
1. Crear Entity `User` con ValueObjects (Email, Password)
2. Crear Application Services
3. Crear Controllers
4. Implementar Testing

---

## 9. CHECKLIST DE REVISIÓN

Antes de dar por terminada una Entity o ValueObject:

- [ ] ¿Usa `declare(strict_types=1)`?
- [ ] ¿Las propiedades son `private`?
- [ ] ¿Usa `DateTimeImmutable` (no `DateTime`)?
- [ ] ¿Los VOs validan UTF-8?
- [ ] ¿Los VOs detectan HTML tags?
- [ ] ¿Los parámetros opcionales están al final?
- [ ] ¿El nombre del archivo coincide con la clase?
- [ ] ¿No hay setters públicos?
- [ ] ¿Los métodos de dominio expresan intención? (activar vs setActivo)
- [ ] ¿Existe el Enum/VO que se referencia?

---

## 10. GLOSARIO

| Término | Definición |
|---------|------------|
| **Entity** | Objeto con identidad única que persiste en el tiempo |
| **Value Object** | Objeto inmutable definido por sus valores, sin identidad |
| **Aggregate** | Grupo de objetos tratados como unidad |
| **Repository** | Abstracción para persistir/recuperar aggregates |
| **Hidratación** | Convertir datos planos en objetos de dominio |
| **Invariante** | Regla que siempre debe cumplirse |
| **Soft Delete** | Marcar como eliminado sin borrar físicamente |
| **PSR-4** | Estándar de autoloading en PHP |

---

*Documento generado como referencia de aprendizaje DDD*
