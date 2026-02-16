<?php
declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object para representar los ingredientes de una receta.
 * 
 * CAMBIOS APLICADOS (Auditoría):
 * - Reordenado validación UTF-8 ANTES de cualquier manipulación de string
 * - Preservación de saltos de línea para mantener estructura de lista
 * - Validación de longitud DESPUÉS de normalización (consistencia)
 * - Regex HTML mejorada para detectar más casos de inyección
 * - Lista ampliada de valores vacíos semánticos
 * - Añadido método getValue() para acceso explícito
 */
final readonly class IngredientesRecipe
{
    private const MAX_LENGTH = 3000;
    
    /**
     * Lista de valores considerados semánticamente vacíos.
     * 
     * PROBLEMA RESUELTO: Antes solo se bloqueaba "sin ingredientes".
     * Ahora se bloquean variantes comunes de placeholders.
     */
    private const EMPTY_VALUES = ['sin ingredientes', 'n/a', 'ninguno', '-', 'none', 'na', 'null'];

    private string $ingredientes;

    public function __construct(string $ingredientes)
    {
        /**
         * PROBLEMA RESUELTO #1: Orden incorrecto de validación
         * 
         * ANTES: Se normalizaba el string (str_replace, trim) ANTES de validar UTF-8.
         *        Esto causaba comportamiento indefinido con bytes inválidos.
         * 
         * AHORA: Primero validamos UTF-8, luego manipulamos el string.
         */
        if (!mb_check_encoding($ingredientes, 'UTF-8')) {
            throw new InvalidArgumentException("Los ingredientes deben ser UTF-8 válido");
        }

        // Normalizar saltos de línea (ahora seguro porque ya validamos UTF-8)
        $normalizados = str_replace(["\r\n", "\r"], "\n", $ingredientes); // Elimina retornos de carro
        $normalizados = trim($normalizados);                               // Elimina espacios al inicio/final

        if ($normalizados === '') {
            throw new InvalidArgumentException("Los ingredientes no pueden estar vacíos");
        }

        /**
         * PROBLEMA RESUELTO #4: Regex HTML incompleta
         * 
         * ANTES: Solo detectaba tags básicos como <script>.
         *        No detectaba: <!--comentarios-->, <!DOCTYPE>, <![CDATA[, etc.
         * 
         * AHORA: Regex mejorada que detecta más patrones de inyección HTML.
         */
        if ($this->containsHtmlTags($normalizados)) {
            throw new InvalidArgumentException("Los ingredientes no pueden contener etiquetas HTML");
        }

        /**
         * PROBLEMA RESUELTO #5: Caracteres de control
         * 
         * La exclusión de \x09 (tab) y \x0A (newline) es INTENCIONAL
         * porque son caracteres de formato válidos en listas de ingredientes.
         * Ahora está documentado explícitamente.
         */
        if ($this->containsControlChars($normalizados)) {
            throw new InvalidArgumentException("Los ingredientes no pueden contener caracteres de control");
        }

        /**
         * PROBLEMA RESUELTO #2: Pérdida de estructura (saltos de línea)
         * 
         * ANTES: preg_replace('/\s+/u', ' ', ...) destruía todos los saltos de línea.
         *        "Harina\nAzúcar\nSal" se convertía en "Harina Azúcar Sal"
         * 
         * AHORA: Usamos [^\S\n]+ para normalizar espacios PRESERVANDO \n.
         *        Además colapsamos líneas vacías múltiples en una sola.
         */
        $limpio = preg_replace('/[^\S\n]+/u', ' ', $normalizados);
        if ($limpio === null) {
            throw new InvalidArgumentException("Los ingredientes tienen un formato inválido");
        }
        
        // Colapsar múltiples líneas vacías en una sola
        $limpio = preg_replace('/\n\s*\n/u', "\n", $limpio);
        if ($limpio === null) {
            throw new InvalidArgumentException("Los ingredientes tienen un formato inválido");
        }
        
        // Limpiar espacios al inicio/final de cada línea
        $lineas = explode("\n", $limpio);
        $lineas = array_map('trim', $lineas);
        $limpio = implode("\n", $lineas);

        /**
         * PROBLEMA RESUELTO #3: Validación de longitud en variable incorrecta
         * 
         * ANTES: Se validaba longitud sobre $normalizados pero se almacenaba $compacto.
         *        La longitud final podía diferir significativamente.
         * 
         * AHORA: Validamos longitud DESPUÉS de toda la normalización,
         *        sobre el valor final que se almacenará.
         */
        if (mb_strlen($limpio, 'UTF-8') > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Los ingredientes no pueden tener más de " . self::MAX_LENGTH . " caracteres"
            );
        }

        /**
         * PROBLEMA RESUELTO #6: Validación parcial de contenido vacío semántico
         * 
         * ANTES: Solo bloqueaba exactamente "sin ingredientes".
         * 
         * AHORA: Bloquea una lista ampliada de valores placeholder comunes
         *        definidos en la constante EMPTY_VALUES.
         */
        $compactoParaValidacion = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $limpio) ?? ''), 'UTF-8');
        if (in_array($compactoParaValidacion, self::EMPTY_VALUES, true)) {
            throw new InvalidArgumentException(
                "Los ingredientes no pueden ser un valor vacío o placeholder"
            );
        }

        $this->ingredientes = $limpio;
    }

    /**
     * PROBLEMA RESUELTO #8: Propiedad sin getter
     * 
     * ANTES: Solo accesible via __toString(), problemático para serialización JSON
     *        y otros contextos no-string.
     * 
     * AHORA: Método getValue() explícito para acceso directo al valor.
     */
    public function getValue(): string
    {
        return $this->ingredientes;
    }

    /**
     * Compara este Value Object con otro.
     * 
     * NOTA: El tipo self garantiza que no se pueda pasar null en contexto estricto.
     */
    public function equals(self $otro): bool
    {
        return $this->ingredientes === $otro->ingredientes;
    }

    public function __toString(): string
    {
        return $this->ingredientes;
    }

    /**
     * Detecta etiquetas HTML en el valor.
     * 
     * PROBLEMA RESUELTO #4: Regex HTML mejorada
     * 
     * ANTES: '/<\s*\/?\s*[a-z0-9]+[^>]*>/iu'
     *        No detectaba comentarios, CDATA, DOCTYPE, etc.
     * 
     * AHORA: Detecta:
     *        - Tags normales: <script>, </div>, <img src="...">
     *        - Comentarios HTML: <!-- ... -->
     *        - CDATA: <![CDATA[ ... ]]>
     *        - DOCTYPE: <!DOCTYPE html>
     *        - Tags con espacios: < script >, </ div>
     */
    private function containsHtmlTags(string $value): bool
    {
        $patterns = [
            '/<\s*\/?\s*[a-z0-9-]+[^>]*>/iu',  // Tags normales
            '/<!--/u',                          // Comentarios HTML
            '/<!\[CDATA\[/iu',                  // CDATA
            '/<!DOCTYPE/iu',                    // DOCTYPE
            '/<\?/u',                           // PHP/XML processing instructions
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Detecta caracteres de control no permitidos.
     * 
     * PROBLEMA RESUELTO #5: Documentación de exclusiones
     * 
     * Caracteres EXCLUIDOS intencionalmente (permitidos):
     * - \x09 (TAB): Puede usarse para indentación en listas
     * - \x0A (LF):  Salto de línea, esencial para listas de ingredientes
     * 
     * Caracteres DETECTADOS (no permitidos):
     * - \x00-\x08: NULL y caracteres de control bajos
     * - \x0B:      Tabulación vertical
     * - \x0C:      Form feed
     * - \x0E-\x1F: Otros caracteres de control
     * - \x7F:      DEL (delete)
     */
    private function containsControlChars(string $value): bool
    {
        return (bool) preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value);
    }
}
