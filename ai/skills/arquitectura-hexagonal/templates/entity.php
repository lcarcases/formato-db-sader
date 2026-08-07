<?php

declare(strict_types=1);

namespace App\Core\{{Module}}/Domain\Entities;

use App\Core\{{Module}}\Domain\ValueObjects\{{ValueObject}};
use App\Core\{{Module}}\Domain\Enums\{{StatusEnum}};
use App\Core\{{Module}}\Domain\Exceptions\{{DomainException}};

/**
 * {{Entity}}
 * 
 * ⚠️ CRITICAL: This must be a RICH DOMAIN MODEL (not anemic!)
 * 
 * An Entity MUST HAVE:
 * ✅ Business behavior (methods that enforce business rules)
 * ✅ Invariant protection (constructor validates business rules)
 * ✅ State management (lifecycle and state transitions)
 * 
 * An Entity MUST NOT:
 * ❌ Have only getters/setters (that's anemic!)
 * ❌ Have only toArray/toJson (that's NOT business logic!)
 * ❌ Be just a data holder (use DTO instead!)
 * 
 * Read: ENTITY_VS_DTO_DECISION_GUIDE.md
 */
final class {{Entity}}
{
    private int $id;
    private string $atributo1;
    private {{ValueObject}} $valueObject;
    private {{StatusEnum}} $estado;
    private ?\DateTimeImmutable $fechaModificacion;

    /**
     * Invariant protection: Validate business rules in constructor
     * 
     * ✅ CORRECT: This ensures the entity is ALWAYS in a valid state
     */
    public function __construct(
        int $id,
        string $atributo1,
        {{ValueObject}} $valueObject,
        {{StatusEnum}} $estado
    ) {
        // ✅ Business rule validation
        if (strlen($atributo1) < 3) {
            throw new {{DomainException}}('Atributo1 debe tener al menos 3 caracteres');
        }
        
        $this->id = $id;
        $this->atributo1 = $atributo1;
        $this->valueObject = $valueObject;
        $this->estado = $estado;
        $this->fechaModificacion = null;
    }

    // ========================================
    // ✅ BUSINESS BEHAVIOR - State Transitions
    // ========================================
    
    /**
     * Activar entity
     * 
     * ✅ CORRECT: Business method that enforces state transition rules
     */
    public function activar(): void
    {
        if ($this->estado === {{StatusEnum}}::ACTIVO) {
            throw new {{DomainException}}('La entidad ya está activa');
        }
        
        $this->estado = {{StatusEnum}}::ACTIVO;
        $this->fechaModificacion = new \DateTimeImmutable();
    }
    
    /**
     * Desactivar entity
     * 
     * ✅ CORRECT: Business method with precondition checking
     */
    public function desactivar(): void
    {
        if (!$this->puedeSerDesactivada()) {
            throw new {{DomainException}}('La entidad no puede ser desactivada en su estado actual');
        }
        
        $this->estado = {{StatusEnum}}::INACTIVO;
        $this->fechaModificacion = new \DateTimeImmutable();
    }

    // ========================================
    // ✅ BUSINESS BEHAVIOR - Business Rules
    // ========================================
    
    /**
     * Verificar si puede ser desactivada
     * 
     * ✅ CORRECT: Business rule query method
     */
    public function puedeSerDesactivada(): bool
    {
        return $this->estado === {{StatusEnum}}::ACTIVO 
            && $this->valueObject->esValido();
    }
    
    /**
     * Verificar si está activa
     * 
     * ✅ CORRECT: Domain query method
     */
    public function estaActiva(): bool
    {
        return $this->estado === {{StatusEnum}}::ACTIVO;
    }

    /**
     * Ejecutar una regla de negocio específica
     * 
     * ✅ CORRECT: Encapsulates business logic within the entity
     */
    public function ejecutarReglaNegocio(): void
    {
        // ✅ Business logic that validates and transforms entity state
        if (!$this->cumpleRequisitos()) {
            throw new {{DomainException}}('La entidad no cumple los requisitos');
        }
        
        // ✅ Apply business transformation
        $this->atributo1 = strtoupper($this->atributo1);
        $this->fechaModificacion = new \DateTimeImmutable();
    }
    
    /**
     * Verificar si cumple requisitos de negocio
     * 
     * ✅ CORRECT: Private business rule method
     */
    private function cumpleRequisitos(): bool
    {
        return strlen($this->atributo1) >= 5 && $this->valueObject->esValido();
    }

    // ========================================
    // ✅ BUSINESS BEHAVIOR - Modifications
    // ========================================
    
    /**
     * Cambiar atributo1 con validación de negocio
     * 
     * ✅ CORRECT: Named business method, not a simple setter
     */
    public function cambiarAtributo1(string $nuevoValor): void
    {
        if (!$this->estaActiva()) {
            throw new {{DomainException}}('No se puede modificar una entidad inactiva');
        }
        
        if (strlen($nuevoValor) < 3) {
            throw new {{DomainException}}('Atributo1 debe tener al menos 3 caracteres');
        }
        
        $this->atributo1 = $nuevoValor;
        $this->fechaModificacion = new \DateTimeImmutable();
    }

    // ========================================
    // ⚠️ Simple Getters (Allowed but minimal)
    // ========================================
    
    /**
     * ⚠️ NOTE: Simple getters are allowed but the entity MUST have
     * business behavior beyond these!
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getAtributo1(): string
    {
        return $this->atributo1;
    }

    public function getValueObject(): {{ValueObject}}
    {
        return $this->valueObject;
    }

    public function getEstado(): {{StatusEnum}}
    {
        return $this->estado;
    }
}

/**
 * ❌ WRONG EXAMPLE - Anemic Entity (DON'T DO THIS!)
 * 
 * class {{Entity}}Anemic
 * {
 *     private int $id;
 *     private string $atributo1;
 *     
 *     // ❌ Only getters - NO BUSINESS LOGIC!
 *     public function getId(): int { return $this->id; }
 *     public function getAtributo1(): string { return $this->atributo1; }
 *     
 *     // ❌ Simple setter - NO VALIDATION!
 *     public function setAtributo1(string $valor): void {
 *         $this->atributo1 = $valor;
 *     }
 *     
 *     // ❌ toArray is NOT business logic!
 *     public function toArray(): array {
 *         return ['id' => $this->id, 'atributo1' => $this->atributo1];
 *     }
 * }
 * 
 * ☝️ This is NOT an Entity! Use a DTO instead!
 */
