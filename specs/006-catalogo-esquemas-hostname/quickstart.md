# Quickstart: Catálogo de Esquemas por Hostname

**Feature**: 006-catalogo-esquemas-hostname

This guide validates the feature end-to-end once implementation is complete. See
[data-model.md](data-model.md) for schema/class details and
[contracts/esquemas-api.md](contracts/esquemas-api.md) for the full response contract.

## Prerequisites

```bash
cd /var/www/html/localhost/formato-db-sader
composer install
cp .env.example .env   # if not already present; set DB_*_PGSQL vars per docker-compose.yml
docker-compose up -d   # Postgres 16, Redis 7.4
```

## 1. Run migrations

```bash
php artisan migrate
```

Expected: 4 new migrations run without error —
`2026_08_30_000001_create_tb_cat_esquema_table`,
`2026_08_30_000002_seed_tb_cat_esquema_table`,
`2026_08_30_000003_create_tb_r_hostname_esquema_table`,
`2026_08_30_000004_seed_tb_r_hostname_esquema_table`.

## 2. Verify seed data

```bash
php artisan tinker --execute="
echo \App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\EsquemaModel::count() . ' esquemas' . PHP_EOL;
echo \App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameEsquemaModel::count() . ' asociaciones' . PHP_EOL;
"
```

Expected: `16 esquemas` and `48 asociaciones`.

## 3. Start the server

```bash
php artisan serve
```

## 4. Exercise the endpoints

```bash
# Catálogo completo (16 esquemas activos, sin "Todos")
curl -s http://127.0.0.1:8000/api/v1/admin/esquemas | jq

# Esquemas de un hostname CON asociaciones (sridesbds09, id 2) — "Todos" + 16 esquemas
curl -s http://127.0.0.1:8000/api/v1/admin/hostnames/2/esquemas | jq

# Esquemas de un hostname SIN asociaciones (pgrdesbds09, id 1) — solo "Todos"
curl -s http://127.0.0.1:8000/api/v1/admin/hostnames/1/esquemas | jq

# Hostname inexistente — 404
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/api/v1/admin/hostnames/999/esquemas
```

Expected outcomes match [contracts/esquemas-api.md](contracts/esquemas-api.md) exactly.

## 5. Run the automated test suite

```bash
./vendor/bin/phpunit --filter EsquemaVOTest
./vendor/bin/phpunit --filter ObtenerEsquemasUseCaseTest
./vendor/bin/phpunit --filter ObtenerEsquemasPorHostnameUseCaseTest
./vendor/bin/phpunit tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/EsquemaRepositoryIntegrationTest.php
./vendor/bin/phpunit tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/EsquemaOutAdapterIntegrationTest.php
./vendor/bin/phpunit --filter ObtenerEsquemasApiTest
./vendor/bin/phpunit --filter ObtenerEsquemasPorHostnameApiTest

# Full suite
./vendor/bin/phpunit
```

## 6. Static analysis & formatting

```bash
./vendor/bin/phpstan analyse
./vendor/bin/pint --test
```

## Success Criteria Checklist (mirrors spec.md)

- [ ] `tb_cat_esquema` contains 16 rows, all `ind_activo = 1`, in the seeded order.
- [ ] `tb_r_hostname_esquema` contains 48 rows (16 esquemas × hostnames 2, 4, 7), all `ind_activo = 1`.
- [ ] `GET /api/v1/admin/esquemas` returns 200 with the 16 esquemas, no "Todos" entry.
- [ ] `GET /api/v1/admin/hostnames/{id}/esquemas` for id 2, 4, 7 returns 200 with "Todos" + 16 esquemas.
- [ ] `GET /api/v1/admin/hostnames/{id}/esquemas` for any other seeded hostname id returns 200 with only "Todos".
- [ ] `GET /api/v1/admin/hostnames/999/esquemas` returns 404 with `success: false`.
- [ ] All new unit/integration/feature tests pass alongside the existing suite.
- [ ] `EsquemaVO`/`HostnameNotFoundException` import zero `Illuminate\*` classes.
