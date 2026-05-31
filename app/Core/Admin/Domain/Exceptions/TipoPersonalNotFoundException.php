<?php

declare(strict_types=1);

namespace App\Core\Admin\Domain\Exceptions;

/**
 * TipoPersonalNotFoundException
 *
 * Domain exception thrown when a TipoPersonal cannot be found.
 *
 * 🔴 DOMAIN EXCEPTION PATTERN:
 * ✅ Extends base Exception (NOT Laravel-specific exception)
 * ✅ Located in Domain layer (pure PHP, zero framework dependencies)
 * ✅ Represents business error (not technical error like DB connection)
 * ✅ Provides context via exception message
 *
 * Usage:
 * - Thrown by Repositories when TipoPersonal with given criteria doesn't exist
 * - Caught by Use Cases and rethrown or handled as business logic
 * - Converted to HTTP 404 response by InAdapter
 *
 * Example:
 * ```php
 * if ($tipoPersonal === null) {
 *     throw new TipoPersonalNotFoundException($id);
 * }
 * ```
 */
final class TipoPersonalNotFoundException extends \Exception
{
    /**
     * Create exception for when TipoPersonal is not found by ID
     *
     * @param  int  $id  The ID that was not found
     * @param  int  $code  HTTP status code (default 404)
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(
        int $id,
        int $code = 404,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'TipoPersonal with ID %d not found. Verify the ID exists in tb_cat_tipo_personal table.',
            $id
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for general not found scenario
     *
     * @param  string  $criteria  Search criteria description
     */
    public static function withCriteria(string $criteria): self
    {
        $exception = new self(0);
        $exception->message = sprintf(
            'TipoPersonal not found with criteria: %s',
            $criteria
        );

        return $exception;
    }

    /**
     * Create exception when no active TipoPersonal records exist
     */
    public static function noActiveTiposFound(): self
    {
        $exception = new self(0);
        $exception->message = 'No active TipoPersonal records found in the system. Database may be empty or all records are inactive.';

        return $exception;
    }
}
