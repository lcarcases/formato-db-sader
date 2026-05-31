<?php

declare(strict_types=1);

namespace App\Core\{{Module}}\Application\UseCases;

use App\Core\{{Module}}\Application\Dtos\In\{{InDto}};
use App\Core\{{Module}}\Application\Ports\Out\{{OutPort}};
use App\Core\{{Module}}\Domain\Entities\{{Entity}};
use App\Core\{{Module}}\Domain\Exceptions\{{DomainException}};

/**
 * {{UseCase}}
 * 
 * 🚨 CRITICAL UseCase Rules:
 * ✅ MUST: Use Spanish verb names (Obtener, Crear, Actualizar, NOT Get, Create, Update)
 * ✅ MUST: Return RAW arrays (for maximum reusability, NOT DTOs)
 * ✅ MUST: Throw exceptions (don't catch them - let InAdapter handle)
 * ✅ MUST: Depend on OutPort interfaces (never on concrete Repositories or OutAdapters)
 * ✅ MUST: Use standard constructor (NOT private readonly)
 * ✅ MUST: Declare private property separately from constructor
 * 
 * ❌ MUST NOT: Implement InPort interface UNLESS using Decorator pattern
 * ❌ MUST NOT: Return DTOs (reduces reusability - use raw arrays instead)
 * ❌ MUST NOT: Catch exceptions (only InAdapter catches exceptions)
 * ❌ MUST NOT: Use `private readonly` in constructor
 * ❌ MUST NOT: Use English verbs in naming (Get, Create, etc.)
 * ❌ MUST NOT: Depend directly on Repositories (use OutPort interfaces)
 */

// ✅ CORRECT: NO interface implementation for simple CRUD
final class {{UseCase}}
{
    // ✅ Declare private property separately
    private {{OutPort}} $outPort;

    // ✅ Standard constructor (NOT private readonly)
    public function __construct(
        {{OutPort}} $outPort
    ) {
        $this->outPort = $outPort;
    }

    // ✅ Returns RAW array (not DTO!)
    // ✅ Throws exceptions (doesn't catch them)
    public function ejecutar({{InDto}} $dto): array
    {
        // 1. Validaciones de negocio (throw exceptions if invalid)
        $this->validar($dto);

        // 2. Recuperar datos a través del OutPort
        $datos = $this->outPort->buscarPor{{Criterio}}($dto->criterio);

        // 3. Procesar lógica de negocio
        // ... (call domain services, entities, etc.)

        // 4. Persistir a través del OutPort (if needed)
        // $this->outPort->guardar($datos);

        // 5. Retornar RAW array (NOT DTO!)
        return $datos;
        
        // OR with more structure:
        // return [
        //     'id' => $datos['id'],
        //     'atributo1' => $datos['atributo1'],
        //     'resultado' => 'success'
        // ];
    }

    private function validar({{InDto}} $dto): void
    {
        if (empty($dto->atributo1)) {
            // ✅ Throw exception (InAdapter will catch)
            throw new {{DomainException}}('El atributo1 es requerido');
        }
    }
}

/**
 * ❌ WRONG PATTERNS - DO NOT DO THESE!
 */
/*
// ❌ WRONG: Implementing interface when not using Decorator
final class {{UseCase}} implements I{{UseCase}}InPort
{
    // ❌ Interface not needed for simple CRUD!
}

// ❌ WRONG: Using private readonly pattern
public function __construct(
    private readonly {{OutPort}} $outPort
) {}

// ❌ WRONG: Returning DTO
public function ejecutar({{InDto}} $dto): {{OutDto}}
{
    $data = $this->outPort->buscar();
    return new {{OutDto}}($data, true, 'Success');  // ❌ Not reusable!
}

// ❌ WRONG: Catching exceptions
public function ejecutar({{InDto}} $dto): array
{
    try {
        return $this->outPort->buscar();
    } catch (\Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];  // ❌ Don't catch!
    }
}

// ❌ WRONG: Depending on Repository directly
public function __construct(
    private {{Entity}}Repository $repository  // ❌ Use OutPort interface!
) {}
*/

/**
 * ✅ WHEN TO IMPLEMENT INTERFACE (Decorator Pattern)
 * 
 * ONLY implement InPort interface when:
 * - Multiple inserts/updates in sequence
 * - Transaction management needed
 * - Cross-cutting concerns (logging, caching, validation)
 */
/*
// ✅ USE interface when Decorator pattern is needed
final class CrearSolicitudConDocumentosUseCase implements ICrearSolicitudInPort
{
    private ISolicitudOutPort $solicitudOutPort;
    private IDocumentoOutPort $documentoOutPort;

    public function __construct(
        ISolicitudOutPort $solicitudOutPort,
        IDocumentoOutPort $documentoOutPort
    ) {
        $this->solicitudOutPort = $solicitudOutPort;
        $this->documentoOutPort = $documentoOutPort;
    }

    public function ejecutar(CrearSolicitudInDto $dto): array
    {
        // Multiple operations - could be decorated with transaction, logging, etc.
        $solicitudId = $this->solicitudOutPort->crear($dto->solicitudData);
        
        foreach ($dto->documentos as $doc) {
            $this->documentoOutPort->adjuntar($solicitudId, $doc);
        }
        
        return ['solicitud_id' => $solicitudId];
    }
}
*/
