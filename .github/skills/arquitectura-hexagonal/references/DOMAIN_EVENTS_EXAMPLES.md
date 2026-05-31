## 3.8 Create Domain Events (things that happened)

**Template:** Use [templates/domain-event.php](../templates/domain-event.php) as a starting structure.

```php
// filepath: app/Core/Programa/Domain/Events/SolicitudAprobadaEvent.php
class SolicitudAprobadaEvent
{
    public function __construct(
        public readonly int $solicitudId,
        public readonly string $folio,
        public readonly DateTimeImmutable $fechaAprobacion
    ) {}
}
```
