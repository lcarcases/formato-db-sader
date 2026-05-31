<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\DTOs\Out\ObtenerTiposPersonalOutDto;
use App\Core\Admin\Application\UseCases\ObtenerTiposPersonalUseCase;
use App\Core\Shared\Infrastructure\Respuesta;
use Illuminate\Http\JsonResponse;

/**
 * ObtenerTiposPersonalInAdapter
 *
 * REST API entry point (InAdapter) for retrieving Tipos de Personal.
 *
 * 🚨 CRITICAL INADAPTER PATTERN (6 MANDATORY RULES):
 *
 * ✅ RULE 1: SPANISH NAMING
 * - Class name: ObtenerTiposPersonalInAdapter (uses PLURAL "TiposPersonal")
 * - Spanish verb: "Obtener" (NOT "Get")
 * - Suffix: "InAdapter" (NOT "Controller")
 *
 * ✅ RULE 2: app()->make() PATTERN
 * - Constructor uses app()->make() to resolve UseCase
 * - NO dependency injection parameters in constructor
 * - Private property declared BEFORE constructor
 *
 * ✅ RULE 3: RESPUESTA CLASS
 * - Import: use App\Core\Shared\Infrastructure\Respuesta;
 * - Create instance: $respuesta = new Respuesta();
 * - Set properties: setSuccess(), setMessage(), setData()
 * - Return: successResponse() or errorResponse($ex)
 *
 * ✅ RULE 4: TRY-CATCH WRAPPING
 * - All __invoke() logic wrapped in try-catch
 * - Success path: setSuccess(true), setMessage(), setData()
 * - Error path: setSuccess(false), setData([]), errorResponse($ex)
 *
 * ✅ RULE 5: CORRECT IMPORT PATH
 * - Uses "Infrastructure" (NOT "Infraestructure")
 * - Path: App\Core\Shared\Infrastructure\Respuesta
 *
 * ✅ RULE 6: PROPERTY DECLARATION
 * - Private property declared before constructor
 * - NOT using private readonly pattern
 *
 * See: .github/skills/arquitectura-hexagonal/references/INADAPTER_MANDATORY_CHECKLIST.md
 *
 * Flow: HTTP Request → InAdapter → UseCase → OutPort → OutAdapter → Repository → DB
 * Response: DB → Repository → OutAdapter → UseCase → InAdapter → Respuesta → JSON
 */
final class ObtenerTiposPersonalInAdapter
{
    private ObtenerTiposPersonalUseCase $obtenerTiposPersonalUseCase;

    public function __construct()
    {
        try {
            $this->obtenerTiposPersonalUseCase = app()->make(ObtenerTiposPersonalUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Handle the incoming request
     *
     * Endpoint: GET /api/v1/admin/tipos-personal
     *
     * Success Response (200):
     * {
     *   "success": true,
     *   "message": "Tipos de personal obtenidos exitosamente.",
     *   "code": 200,
     *   "data": {
     *     "tiposPersonal": [
     *       {"id": 1, "nombre": "Base"},
     *       {"id": 2, "nombre": "Enlace"},
     *       {"id": 3, "nombre": "Confianza"},
     *       {"id": 4, "nombre": "Externo"}
     *     ]
     *   }
     * }
     *
     * Error Response (500):
     * {
     *   "success": false,
     *   "message": "Error al obtener los tipos de personal.",
     *   "code": 500,
     *   "data": []
     * }
     *
     * @return JsonResponse
     */
    public function __invoke()
    {
        $respuesta = new Respuesta;

        try {
            // Execute use case - returns raw array
            $rawData = $this->obtenerTiposPersonalUseCase->ejecutar();

            // Convert to OutDTO for type safety
            $obtenerTiposPersonalOutDto = ObtenerTiposPersonalOutDto::fromArray($rawData);

            // Set response properties
            $respuesta->setSuccess(true);
            $respuesta->setMessage('Tipos de personal obtenidos exitosamente.');
            $respuesta->setData($obtenerTiposPersonalOutDto);

            return $respuesta->successResponse();

        } catch (\Exception $ex) {
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage('Error al obtener los tipos de personal.');

            return $respuesta->errorResponse($ex);
        }
    }
}
