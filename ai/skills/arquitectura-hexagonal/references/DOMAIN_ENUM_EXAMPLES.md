**Template:** Use [templates/enum.php](../templates/enum.php) as a starting structure.

```php
// filepath: app/Core/Programa/Domain/Enums/EstatusSolicitudEnum.php
enum EstatusSolicitudEnum: string
{
    case PENDIENTE = 'pendiente';
    case EN_REVISION = 'en_revision';
    case APROBADA = 'aprobada';
    case RECHAZADA = 'rechazada';
    case CANCELADA = 'cancelada';
}
```