<?php

namespace App\Core\{{Module}}\Infrastructure\Adapters\In\Api;

use App\Core\{{Module}}\Application\Dtos\In\{{InDto}};
use App\Core\{{Module}}\Application\UseCases\{{UseCase}}UseCase;
use App\Core\Shared\Infraestructure\Respuesta;
use Illuminate\Http\Request;

/**
 * {{VerbSpanish}}{{NounSpanish}}InAdapter
 * 
 * Entry point adapter for {{description}} via REST API.
 * Translates HTTP requests into use case execution.
 * 
 * IMPORTANT PATTERNS:
 * - Use app()->make() for dependency resolution in constructor
 * - Wrap all logic in try-catch blocks
 * - Use Respuesta class for standardized responses
 * - Always name with Spanish verb + noun + InAdapter suffix
 */
class {{VerbSpanish}}{{NounSpanish}}InAdapter
{
    private {{UseCase}}UseCase ${{useCaseCamelCase}}UseCase;

    public function __construct()
    {
        try {
            $this->{{useCaseCamelCase}}UseCase = app()->make({{UseCase}}UseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Handle the incoming request
     * 
     * @param Request $request Laravel HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function {{methodName}}(Request $request)
    {
        try {
            // 1. Create Respuesta instance
            $respuesta = new Respuesta();

            // 2. Extract and validate request data
            $request->validate([
                'campo1' => 'required|string',
                'campo2' => 'required|integer',
            ]);

            // 3. Create InDto from request
            ${{inDtoCamelCase}} = new {{InDto}}(
                campo1: $request->input('campo1'),
                campo2: (int) $request->input('campo2')
            );

            // 4. Execute use case
            \Log::info("Executing {{UseCase}}");
            ${{outDtoCamelCase}} = $this->{{useCaseCamelCase}}UseCase->{{useCaseMethod}}(${{inDtoCamelCase}});
            \Log::info("{{UseCase}} executed successfully");

            // 5. Set up successful response
            $respuesta->setSuccess(true);
            $respuesta->setMessage("{{Success message in Spanish}}");
            $respuesta->setData(${{outDtoCamelCase}});

            // 6. Return successful response
            return $respuesta->successResponse();

        } catch (\Exception $ex) {
            // Handle errors with standardized error response
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage("{{Error message in Spanish}}");
            return $respuesta->errorResponse($ex);
        }
    }
}
