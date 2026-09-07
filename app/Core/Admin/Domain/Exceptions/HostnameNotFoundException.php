<?php

declare(strict_types=1);

namespace App\Core\Admin\Domain\Exceptions;

/**
 * HostnameNotFoundException
 *
 * Domain exception thrown when a Hostname cannot be found.
 *
 * 🔴 DOMAIN EXCEPTION PATTERN:
 * ✅ Extends base Exception (NOT Laravel-specific exception)
 * ✅ Located in Domain layer (pure PHP, zero framework dependencies)
 * ✅ Represents business error (not technical error like DB connection)
 * ✅ Provides context via exception message
 *
 * Usage:
 * - Thrown by ObtenerEsquemasPorHostnameUseCase when EsquemaOutPort::obtenerEsquemasPorHostname()
 *   signals the given idHostname does not exist in tb_cat_hostname (returns null)
 * - Caught by the InAdapter and translated to HTTP 404 response
 *
 * Example:
 * ```php
 * if ($esquemas === null) {
 *     throw new HostnameNotFoundException($idHostname);
 * }
 * ```
 */
final class HostnameNotFoundException extends \Exception
{
    /**
     * Create exception for when Hostname is not found by ID
     *
     * @param  int  $idHostname  The hostname ID that was not found
     * @param  int  $code  HTTP status code (default 404)
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(
        int $idHostname,
        int $code = 404,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Hostname with ID %d not found. Verify the ID exists in tb_cat_hostname table.',
            $idHostname
        );

        parent::__construct($message, $code, $previous);
    }
}
