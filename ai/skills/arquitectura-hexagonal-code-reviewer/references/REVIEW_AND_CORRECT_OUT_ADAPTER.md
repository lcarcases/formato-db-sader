# OutAdapter Review and Correction Checklist (Mandatory)

Use this checklist when reviewing or correcting any OutAdapter. The goal is to keep OutAdapters consistent, predictable, and aligned with the architecture rules.

## 1. Purpose of an OutAdapter

1. An OutAdapter exists to adapt the application to persistence or external technology.
2. It may contain logic related to query construction, batching, mapping, and persistence orchestration.
3. It must not implement business logic that belongs to the Domain or Application layers.
4. It must not contain transport concerns, HTTP logic, or user-facing behavior.

## 2. Allowed Logic

An OutAdapter may contain logic only when it is related to persistence mechanics, such as:

- building complex SQL statements,
- constructing bulk insert/update payloads,
- optimizing network interaction,
- adapting domain data to database structures,
- delegating low-level persistence operations to a repository or driver.

### Correct Example 1: Building a complex update query

```php
public function ejecutarCorrecciones($ejecutarCorreccionesInDto)
{
    try {
        $totalCorrecciones = $ejecutarCorreccionesInDto->totalCorrecciones();
        $idSolicitudes = [];
        $query = "UPDATE ap_inventario_pd.tb_control_beneficiarios SET date_entrega = CASE id_num_solicitud_beneficiario";

        for ($i = 0; $i < $totalCorrecciones; $i++) {
            $idSolicitud = $ejecutarCorreccionesInDto->obtenerIdSolicitudCorreccion($i);
            $idSolicitudes[] = $idSolicitud;
            $fechaEntregaDebeDecir = $ejecutarCorreccionesInDto->obtenerFechaEntregaDebeDecirCorreccion($i);
            $query .= " WHEN $idSolicitud THEN '$fechaEntregaDebeDecir'";
        }

        $query .= " END WHERE id_num_solicitud_beneficiario IN (" . implode(',', $idSolicitudes) . ")";

        $this->correccionBeneficiarioMySQLRepository->ejecutarCorrecciones($query);
    } catch (\Exception $ex) {
        throw $ex;
    }
}
```

### Correct Example 2: Bulk insert payload generation

```php
public function persistirCorreccionesLote($generarSolicitudCorreccionesInDto)
{
    try {
        $correcciones = [];
        $totalCorrecciones = $generarSolicitudCorreccionesInDto->totalCorrecciones();

        for ($i = 0; $i < $totalCorrecciones; $i++) {
            if (!$generarSolicitudCorreccionesInDto->estaExcluida($i)) {
                $correcciones[] = [
                    'ln_acuse_estatal' => $generarSolicitudCorreccionesInDto->obtenerAcuseEstatalCorreccion($i),
                    'id_nu_solicitud' => $generarSolicitudCorreccionesInDto->obtenerIdSolicitudCorreccion($i),
                    'fecha_entrega_dice' => $generarSolicitudCorreccionesInDto->obtenerFechaEntregaDiceCorreccion($i),
                    'fecha_entrega_debe_decir' => $generarSolicitudCorreccionesInDto->obtenerFechaEntregaDebeDecirCorreccion($i),
                    'id_nu_estatus' => 1,
                    'id_nu_lote_benef' => $generarSolicitudCorreccionesInDto->obtenerIdLote(),
                    'sn_usuario_alta' => $generarSolicitudCorreccionesInDto->obtenerUsuarioAlta(),
                ];
            }
        }

        $this->correccionBeneficiarioMySQLRepository->persistirCorreccionesLote($correcciones);
    } catch (\Exception $ex) {
        throw $ex;
    }
}
```

## 3. Forbidden Patterns

1. Business rules inside an OutAdapter.
2. Query execution logic that belongs in the Repository layer.
3. Logging inside OutAdapter catch blocks.
4. HTTP, API, or Response handling in OutAdapter.
5. Direct domain decision-making or validation logic.

## 4. Repository vs OutAdapter Responsibility

1. The Repository is responsible for executing queries and persistence operations.
2. The OutAdapter is responsible for preparing the data and building the persistence request.
3. The OutAdapter should not own the actual execution mechanism beyond the required adaptation layer.

## 5. Catch Block Rules

1. OutAdapter catch blocks must not log errors.
2. OutAdapter catch blocks must rethrow the exception.
3. Logging is the responsibility of the higher layer, typically the InAdapter or Response envelope.

### Forbidden Example

```php
catch (\Exception $e) {
    \Log::error('Error en TipoPersonalPostgresSQLOutAdapter::obtenerTodos', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'adapter' => self::class,
    ]);

    throw $e;
}
```

### Required Example

```php
catch (\Exception $ex) {
    throw $ex;
}
```

## 6. Mandatory Review Actions

For each OutAdapter under review, the agent must verify:

1. The logic is persistence-related and not business logic.
2. The class is not performing domain decisions.
3. Query execution is delegated to the Repository layer.
4. The class does not log exceptions.
5. The class keeps its responsibility limited to adaptation and persistence mechanics.

## 7. Acceptance Criteria

An OutAdapter passes review only if all checks are true:

1. It contains only persistence adaptation logic.
2. It does not implement business rules.
3. Query execution remains in the Repository layer.
4. Catch blocks rethrow exceptions without logging.
5. It remains focused on technology adaptation rather than domain behavior.