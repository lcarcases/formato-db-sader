# Research: Catálogo de Esquemas por Hostname

**Feature**: Catálogo de Esquemas por Hostname
**Date**: 2026-08-30
**Status**: Final

## Overview

Esta feature introduce el primer catálogo del repositorio con una relación muchos-a-muchos
explícita (`Esquema` ↔ `Hostname`). El propósito de este research es confirmar, contra el código
ya mergeado en `specs/004-catalogo-bases-datos` y `specs/005-catalogo-hostnames`, exactamente qué
patrones replicar sin desviación, y decidir el único aspecto genuinamente nuevo (la tabla pivote).

## Decision 1: Esquema de tabla `tb_cat_esquema`

- **Decision**: Misma forma que `tb_cat_hostname`: `id_nu_esquema` (PK serial), `sn_nombre`
  VARCHAR(100) UNIQUE, `ind_activo` SMALLINT DEFAULT 1 CHECK IN (0,1) con índice dedicado,
  `created_at`/`updated_at`, comentario de tabla.
- **Rationale**: Consistencia total con el resto de catálogos (`tb_cat_hostname`,
  `tb_cat_base_datos`, `tb_cat_ambiente_desarrollo`); no hay ningún requisito del spec que motive
  desviación.
- **Alternatives considered**: Ninguna — el spec (Scope) fija esta forma explícitamente.

## Decision 2: Esquema de tabla `tb_r_hostname_esquema` (primera tabla pivote del repo)

- **Decision**: `id_nu_hostname_esquema` (PK serial), `id_nu_hostname` (FK →
  `tb_cat_hostname.id_nu_hostname`), `id_nu_esquema` (FK → `tb_cat_esquema.id_nu_esquema`),
  `ind_activo` SMALLINT DEFAULT 1 CHECK IN (0,1), timestamps, índice único compuesto sobre
  `(id_nu_hostname, id_nu_esquema)`, índice adicional sobre `id_nu_hostname`.
- **Rationale**: No existe precedente de tabla pivote en el repo. Se sigue la convención de
  nomenclatura `tb_{context}_{entity}` (`.specify/memory/constitution.md`, `CLAUDE.md`)
  extendiéndola con el infijo `r` (análogo a `cat` para catálogos) para marcar explícitamente una
  tabla de pura relación, no un catálogo ni una tabla operacional. Un PK surrogado + `ind_activo` +
  timestamps mantiene la forma idéntica a toda otra tabla del repo en vez de inventar una
  convención distinta solo para esta tabla. El índice único compuesto previene asociaciones
  duplicadas; el índice adicional sobre `id_nu_hostname` optimiza la ruta de consulta principal del
  endpoint anidado.
- **Alternatives considered**:
  - *Tabla `many_to_many` estilo Laravel (`hostname_esquema`, sin prefijo `tb_`, sin PK surrogado,
    PK compuesta)*: rechazada por romper la convención `tb_{context}_{entity}` + `id_nu_` de todo
    el repo, y porque el proyecto usa Eloquent como detalle de infraestructura, no como framework
    "opinionado" completo (no hay convenciones Laravel-nativas en ninguna otra tabla).
  - *FKs directas en `tb_cat_esquema` a un solo hostname*: rechazada porque el spec exige
    explícitamente muchos-a-muchos (un esquema puede pertenecer a >1 hostname).

## Decision 3: Migrations en Laravel 13

- **Decision**: 4 archivos de migración nuevos, separados por responsabilidad (igual que 004/005):
  `..._create_tb_cat_esquema_table.php`, `..._seed_tb_cat_esquema_table.php`,
  `..._create_tb_r_hostname_esquema_table.php`, `..._seed_tb_r_hostname_esquema_table.php`.
  Prefijo de fecha `2026_08_30_0000{1..4}` para mantener el orden de ejecución (schema antes que
  seed, `tb_cat_esquema` antes que `tb_r_hostname_esquema` por la FK).
- **Rationale**: Mismo patrón ya validado en 004/005 (`DB::table()->insert()` en migración de seed,
  no un `Seeder` de Laravel — dato siempre presente sin pasos manuales adicionales).
- **Alternatives considered**: Un solo archivo de migración combinando schema+seed — rechazado por
  romper el patrón establecido de separación schema/seed.

## Decision 4: Convención de nombres de columnas

- **Decision**: `id_nu_` prefijo para PKs/FKs numéricas, `sn_` para strings, `ind_` para flags —
  sin excepción para las columnas nuevas (`id_nu_esquema`, `id_nu_hostname_esquema`).
- **Rationale**: Sigue el patrón de todas las tablas existentes.
- **Alternatives considered**: Ninguna.

## Decision 5: Repository pattern — Eloquent crudo vs. Value Object

- **Decision**: `EsquemaRepository` retorna Eloquent models crudos (`list<EsquemaModel>` /
  `list<HostnameEsquemaModel>` según el método), replicando el patrón **real** verificado en
  `HostnameRepository::obtenerHostnames()` (retorna `list<HostnameModel>`). El mapeo a `EsquemaVO`
  ocurre en `EsquemaOutAdapter`, exactamente como `HostnameOutAdapter::obtenerHostnames()` mapea
  `HostnameModel` → `HostnameVO`.
- **Rationale**: `specs/005-catalogo-hostnames/data-model.md` describe (de forma desactualizada)
  un `HostnameRepository` que retorna `HostnameVO` directamente, pero el código real mergeado hace
  el mapeo en el `OutAdapter`. Este plan sigue el código real, no el texto de planeación
  superseded, consistente con la nota de `CLAUDE.md`/`open-questions-response.md` sobre priorizar
  el patrón shippeado.
- **Alternatives considered**: Mapear a VO dentro del Repository — rechazado por no coincidir con
  el código real ya mergeado, que es la fuente de verdad para "mismo patrón que Hostname".

## Decision 6: Verificación de existencia de hostname sin modificar 005

- **Decision**: `EsquemaRepository::obtenerEsquemasPorHostname(int $idHostname): ?array` consulta
  `HostnameModel::query()->find($idHostname)` directamente (mismo bounded context `Admin`, misma
  capa Infrastructure) para determinar si el hostname existe, antes de consultar
  `HostnameEsquemaModel`. Retorna `null` si el hostname no existe.
- **Rationale**: El spec (Stage 0 pregunta 11) cierra explícitamente esta decisión: no modificar
  `HostnameOutPort`/`HostnameOutAdapter` ya mergeados y testeados en 005; usar `HostnameModel`
  directamente es válido porque ambos modelos viven en la misma capa Infrastructure del mismo
  bounded context (no es una violación de capas — Domain/Application siguen sin conocer Eloquent).
- **Alternatives considered**:
  - *Inyectar `HostnameOutPort` en `EsquemaRepository`/`EsquemaOutAdapter`*: rechazada porque
    `HostnameOutPort` no expone un método `obtenerPorId`, y añadirlo modificaría un contrato ya
    mergeado y testeado — fuera de alcance de esta historia.
  - *Nuevo Port `HostnameExistsPort`*: rechazada por sobre-ingeniería; una consulta directa a
    `HostnameModel` desde Infrastructure de la misma bounded context no viola Hexagonal
    Architecture (la violación sería que Application/Domain conocieran Eloquent, lo cual no ocurre
    aquí).

## Decision 7: `EsquemaOutPort` — semántica `null` vs. `[]`

- **Decision**: `obtenerEsquemasPorHostname(int $idHostname): ?array` retorna `null` cuando el
  hostname no existe, y `[]` (lista vacía) cuando existe pero no tiene asociaciones activas.
  `ObtenerEsquemasPorHostnameUseCase` traduce `null` a `HostnameNotFoundException`.
- **Rationale**: Cierra de forma explícita e inequívoca la distinción 404 vs. 200-solo-Todos exigida
  por FR-003/FR-004 del spec, sin sobrecargar el tipo de retorno con una tercera clase de valor
  (p. ej. un DTO de resultado); `null` es la forma más simple y explícita en PHP para "recurso padre
  no encontrado" en un contrato de Application.
- **Alternatives considered**: Lanzar la excepción directamente desde el Repository/OutAdapter —
  rechazada porque el spec exige que Domain (`HostnameNotFoundException`) sea lanzada por el Use
  Case, no por Infrastructure, manteniendo la traducción de "ausencia de datos" → "error de
  dominio" en la capa de Application (Dependency Inversion / capas limpias).

## Decision 8: Entrada sintética "Todos"

- **Decision**: `{id: 0, nombre: "Todos"}` nunca pasa por `EsquemaVO` (cuyo invariante `id > 0` no
  cambia). Se construye directamente como `ObtenerEsquemaOutDto(id: 0, nombre: 'Todos')` dentro de
  `ObtenerEsquemasPorHostnameOutDto`, que la antepone siempre como primer elemento del array
  resultante en `toArray()`.
- **Rationale**: Cierra la decisión ya tomada en el spec/enriched story; mantiene el Domain
  minimalista y libre de conceptos sintéticos/no persistidos.
- **Alternatives considered**: Prepend en el InAdapter en vez del OutDto — funcionalmente
  equivalente, pero se decide colocarlo en el OutDto porque es el componente cuyo nombre
  (`ObtenerEsquemasPorHostnameOutDto`) ya documenta esa responsabilidad explícitamente en el spec
  cerrado (Stage 0 pregunta 13), y mantiene el InAdapter simétrico al de `ObtenerEsquemasInAdapter`.

## Decision 9: Formato de respuesta JSON

- **Decision**: `App\Core\Shared\Infraestructure\Respuesta` (ortografía española),
  `{success, message, data}`, para ambos InAdapters — confirmado leyendo directamente
  `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerHostnamesInAdapter.php` (código real
  mergeado), no el texto de `specs/005-catalogo-hostnames/plan.md` (que describe incorrectamente
  `response()->json()` inline).
- **Rationale**: `CLAUDE.md` indica explícitamente que la elección de clase `Respuesta` es por
  archivo, según lo que el InAdapter que se está tocando ya importa; el precedente inmediato/padre
  de esta historia (`ObtenerHostnamesInAdapter`) usa la variante española.
- **Alternatives considered**: `App\Core\Shared\Infrastructure\Respuesta` (inglés, con mapeo de
  excepciones y código HTTP variable) — rechazada por no coincidir con el precedente inmediato; el
  manejo 404 se resuelve explícitamente en el InAdapter capturando `HostnameNotFoundException` y
  llamando `Respuesta::errorResponse()` con un status override, ver `contracts/esquemas-api.md`
  para el detalle exacto.

## Decision 10: Testing strategy

- **Decision**: Los 4 niveles de test ya usados en 004/005 (Unit VO, Unit UseCase, Integration
  Repository/OutAdapter, Feature/API), agregando explícitamente un `EsquemaVOTest.php` (mirror de
  `AmbienteVOTest.php`, dado que `HostnameVOTest.php` no existe en el repo real pese a estar
  mencionado en `data-model.md` de 005).
- **Rationale**: Cierra la instrucción explícita del enriched story de "si existe un
  `HostnameVOTest.php` equivalente, mirrorlo" — no existe, así que se usa el patrón real más
  cercano (`AmbienteVOTest.php`) para no dejar `EsquemaVO` sin cobertura unitaria de invariantes.
- **Alternatives considered**: Omitir el test de VO — rechazado porque el spec exige explícitamente
  cobertura de invariantes de dominio (Success Criteria, último punto).
