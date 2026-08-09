<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\DTOs\Out\ObtenerBaseDatosOutDto;
use App\Core\Admin\Application\DTOs\Out\ObtenerBasesDatosOutDto;
use App\Core\Admin\Application\UseCases\ObtenerBasesDatosUseCase;
use App\Core\Admin\Domain\ValueObjects\BaseDatosVO;
use Illuminate\Http\JsonResponse;

/**
 * In Adapter (REST Controller) for Obtener Bases de Datos endpoint
 *
 * Handles GET /api/v1/admin/bases-datos request.
 *
 * Responsibilities:
 * - Invoke use case
 * - Create OutDto from use case result
 * - Transform to standard JSON response format
 * - Handle any exceptions (convert to appropriate HTTP responses)
 *
 * Invokable Controller:
 * - Single action controller using __invoke() magic method
 * - No request validation needed (GET endpoint with no parameters)
 */
final readonly class ObtenerBasesDatosInAdapter
{
    public function __construct(
        private ObtenerBasesDatosUseCase $useCase,
    ) {}

    /**
     * Handle the incoming request
     */
    public function __invoke(): JsonResponse
    {
        try {
            // Execute use case to get raw array<BaseDatosVO>
            $basesDatos = $this->useCase->execute();

            // Create DTO (explicit contract) — mapping from domain VO to nested
            // DTO happens here, the only place OutDTOs may be instantiated
            $dto = new ObtenerBasesDatosOutDto(
                array_map(
                    fn (BaseDatosVO $baseDatos): ObtenerBaseDatosOutDto => new ObtenerBaseDatosOutDto(
                        id: $baseDatos->id,
                        nombre: $baseDatos->nombre,
                    ),
                    $basesDatos
                )
            );

            // Transform to standard JSON response format
            return response()->json([
                'data' => $dto->toArray(),
                'message' => 'Bases de datos obtenidas exitosamente',
                'code' => '200',
                'success' => true,
            ], 200);

        } catch (\Throwable $exception) {
            // Log error for debugging
            logger()->error('Error al obtener bases de datos', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Return generic error response
            return response()->json([
                'data' => null,
                'message' => 'Error al obtener bases de datos. Por favor contacte al administrador.',
                'code' => '500',
                'success' => false,
            ], 500);
        }
    }
}
