<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\DTOs\Out\ObtenerAmbientesOutDto;
use App\Core\Admin\Application\UseCases\ObtenerAmbientesUseCase;
use Illuminate\Http\JsonResponse;

/**
 * In Adapter (REST Controller) for Obtener Ambientes endpoint
 *
 * Handles GET /api/v1/admin/ambientes-desarrollo request.
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
final readonly class ObtenerAmbientesInAdapter
{
    public function __construct(
        private ObtenerAmbientesUseCase $useCase,
    ) {}

    /**
     * Handle the incoming request
     */
    public function __invoke(): JsonResponse
    {
        try {
            // Execute use case to get raw array<AmbienteVO>
            $ambientes = $this->useCase->execute();

            // Create DTO (explicit contract)
            $dto = new ObtenerAmbientesOutDto($ambientes);

            // Transform to standard JSON response format
            return response()->json([
                'data' => array_map(
                    fn ($ambiente) => $ambiente->toArray(),
                    $dto->ambientes
                ),
                'message' => 'Ambientes obtenidos exitosamente',
                'code' => '200',
                'success' => true,
            ], 200);

        } catch (\Throwable $exception) {
            // Log error for debugging
            logger()->error('Error al obtener ambientes', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Return generic error response
            return response()->json([
                'data' => null,
                'message' => 'Error al obtener ambientes. Por favor contacte al administrador.',
                'code' => '500',
                'success' => false,
            ], 500);
        }
    }
}
