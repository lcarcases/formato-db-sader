<?php

declare(strict_types=1);

namespace App\Core\{{Module}}\Infrastructure\Adapters\Out\{{Database}};

use App\Core\{{Module}}\Application\Ports\Out\I{{Entity}}OutPort;
use App\Core\{{Module}}\Infrastructure\Adapters\Out\{{Database}}\Repositories\{{Entity}}{{Database}}Repository;

/**
 * {{Entity}}{{Database}}OutAdapter
 * 
 * 🚨 CRITICAL OutAdapter Rules:
 * ✅ MUST: Implement OutPort interface
 * ✅ MUST: Use Repository for data access
 * ✅ MUST: Map raw data ↔ Domain objects
 * ✅ MUST: Be injected in UseCases (via OutPort interface)
 * ✅ MUST: Be bound in ServiceProvider (OutPort → OutAdapter)
 * ✅ MUST: Name the injected Repository property after its class (${nameRepositoryClass}, never $repository)
 *
 * ❌ MUST NOT: Access database directly (use Repository!)
 * ❌ MUST NOT: Be skipped/omitted (OutAdapter is MANDATORY!)
 * ❌ MUST NOT: Contain business logic
 */
final class {{Entity}}{{Database}}OutAdapter implements I{{Entity}}OutPort
{
    // ✅ Uses Repository (NOT extends, just uses)
    public function __construct(
        private {{Entity}}{{Database}}Repository ${{entityCamelCase}}{{Database}}Repository
    ) {}

    /**
     * ✅ Calls Repository and returns data
     */
    public function obtenerTodos(): array
    {
        // Get raw data from Repository
        $rawData = $this->{{entityCamelCase}}{{Database}}Repository->findAll();
        
        // ✅ Option 1: Return as-is (if no transformation needed)
        return $rawData;
        
        // ✅ Option 2: Transform/map data
        // return array_map(
        //     fn($row) => [
        //         'id' => (int) $row->id,
        //         'nombre' => (string) $row->nombre
        //     ],
        //     $rawData
        // );
    }

    /**
     * ✅ Gets raw data from Repository, optionally transforms
     */
    public function buscarPor{{Criterio}}(string $criterio): ?array
    {
        $rawData = $this->{{entityCamelCase}}{{Database}}Repository->findBy{{Criterio}}($criterio);
        
        if (!$rawData) {
            return null;
        }
        
        // ✅ Return raw data or transform as needed
        return [
            'id' => (int) $rawData->id,
            'atributo1' => (string) $rawData->atributo1,
        ];
    }

    /**
     * ✅ Receives data, passes to Repository
     */
    public function crear(array $data): int
    {
        // Transform if needed
        $dataParaInsertar = [
            'atributo1' => $data['atributo1'],
            'atributo2' => $data['atributo2'],
            'created_at' => now()
        ];
        
        // ✅ Repository handles the insert
        return $this->{{entityCamelCase}}{{Database}}Repository->insertar($dataParaInsertar);
    }
}

/**
 * Example with Domain Entity Mapping:
 * 
 * If you need to work with Domain Entities:
 */
/*
use App\Core\{{Module}}\Domain\Entities\{{Entity}}Entity;
use App\Core\{{Module}}\Domain\Vo\{{ValueObject}};

final class {{Entity}}{{Database}}OutAdapter implements I{{Entity}}OutPort
{
    public function __construct(
        private {{Entity}}{{Database}}Repository ${{entityCamelCase}}{{Database}}Repository
    ) {}

    // ✅ Receives Domain Entity, transforms to array, passes to Repository
    public function persistir({{Entity}}Entity $entity): int
    {
        $data = [
            'atributo1' => $entity->getAtributo1(),
            'value_object' => $entity->getValueObject()->valor(),
            'created_at' => now()
        ];
        
        return $this->{{entityCamelCase}}{{Database}}Repository->insertar($data);
    }
    
    // ✅ Gets raw data from Repository, maps to Domain Entity
    public function buscarPorId(int $id): ?{{Entity}}Entity
    {
        $rawData = $this->{{entityCamelCase}}{{Database}}Repository->findById($id);
        
        if (!$rawData) {
            return null;
        }
        
        // ✅ OutAdapter maps raw data → Domain Entity
        return $this->mapToEntity($rawData);
    }
    
    // ✅ Maps raw data to Domain Entity
    private function mapToEntity(object $rawData): {{Entity}}Entity
    {
        return new {{Entity}}Entity(
            id: $rawData->id,
            atributo1: $rawData->atributo1,
            valueObject: new {{ValueObject}}($rawData->value_object)
        );
    }
}
*/
