**Template:** Use [templates/entity.php](../templates/entity.php) as a starting structure.

```php
// filepath: app/Core/Programa/Domain/Entities/SolicitudEntity.php
class SolicitudEntity
{
    private int $id;
    private FolioVO $folio;
    private EstatusSolicitudEnum $estatus;
    
    // ✅ Behavior - NOT just getters/setters
    public function aprobar(): void
    {
        if ($this->estatus !== EstatusSolicitudEnum::EN_REVISION) {
            throw new SolicitudNoAprobableException();
        }
        $this->estatus = EstatusSolicitudEnum::APROBADA;
    }
    
    public function estaAprobada(): bool
    {
        return $this->estatus === EstatusSolicitudEnum::APROBADA;
    }
}
```