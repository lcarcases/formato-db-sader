<?php

declare(strict_types=1);

namespace App\Core\{{Module}}\Infrastructure\Adapters\Out\{{Database}}\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * {{Entity}}{{Database}}Repository
 * 
 * 🚨 CRITICAL Repository Rules:
 * ✅ MUST: Return RAW data (objects/arrays) from database
 * ✅ MUST: Use Laravel Query Builder / Eloquent
 * ✅ MUST: Be simple data access only
 * ✅ MUST: Have NO interface implementation
 * 
 * ❌ MUST NOT: Implement OutPort interfaces (OutAdapter does this!)
 * ❌ MUST NOT: Create/return Domain Entities (OutAdapter does this!)
 * ❌ MUST NOT: Have business logic
 * ❌ MUST NOT: Know about Domain layer
 */
final class {{Entity}}{{Database}}Repository
{
    private string $table = '{{table}}';

    /**
     * ✅ Returns RAW data as array
     */
    public function findAll(): array
    {
        $results = DB::table($this->table)->get();
        
        // ✅ Return raw data (NOT entities!)
        return $results->toArray();
    }

    /**
     * ✅ Returns RAW object or null
     */
    public function findBy{{Criterio}}(string $criterio): ?object
    {
        return DB::table($this->table)
            ->where('{{campo}}', $criterio)
            ->first();
    }

    /**
     * ✅ Returns RAW object or null
     */
    public function findById(int $id): ?object
    {
        return DB::table($this->table)
            ->where('id', $id)
            ->first();
    }

    /**
     * ✅ Inserts and returns ID
     */
    public function insertar(array $data): int
    {
        return DB::table($this->table)->insertGetId($data);
    }

    /**
     * ✅ Updates with raw data
     */
    public function actualizar(int $id, array $data): bool
    {
        return DB::table($this->table)
            ->where('id', $id)
            ->update($data);
    }

    /**
     * ✅ Simple delete
     */
    public function eliminar(int $id): bool
    {
        return DB::table($this->table)
            ->where('id', $id)
            ->delete();
    }
    
    /**
     * ✅ Executes custom query (for OutAdapter-built queries)
     */
    public function ejecutarConsulta(string $query): array
    {
        return DB::select($query);
    }
}

/**
 * ❌ WRONG PATTERN - DO NOT DO THIS!
 * 
 * // ❌ Repository implementing OutPort interface
 * final class {{Entity}}{{Database}}Repository implements I{{Entity}}OutPort
 * {
 *     // ❌ Creating Domain Entities in Repository
 *     public function findAll(): array
 *     {
 *         $results = DB::table('{{table}}')->get();
 *         
 *         return $results->map(function ($row) {
 *             // ❌ Repository should NOT create entities!
 *             return new {{Entity}}Entity(
 *                 id: (int) $row->id,
 *                 nombre: (string) $row->nombre
 *             );
 *         })->toArray();
 *     }
 * }
 */
