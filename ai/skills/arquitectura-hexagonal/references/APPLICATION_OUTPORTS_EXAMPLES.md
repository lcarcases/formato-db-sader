## 4.2 Create OutPorts (what the application NEEDS from external world)

**Template:** Use [templates/out-port.php](../templates/out-port.php) as a starting structure.

OutPorts describe dependencies - they are implemented by OutAdapters.

```php
// filepath: app/Core/Programa/Application/Ports/Out/ISolicitudOutPort.php
interface ISolicitudOutPort
{
    public function persistir(SolicitudEntity $solicitud): int;
    public function buscarPorId(int $id): ?SolicitudEntity;
    public function buscarPorCurp(CurpVO $curp): array;
}

// filepath: app/Core/Programa/Application/Ports/Out/INotificacionOutPort.php
interface INotificacionOutPort
{
    public function enviarCorreo(string $destinatario, string $asunto, string $mensaje): bool;
}

// filepath: app/Core/Programa/Application/Ports/Out/IArchivoOutPort.php
interface IArchivoOutPort
{
    public function guardar(string $contenido, string $ruta): string;
    public function obtener(string $ruta): ?string;
}
```

**RULES:**
```
✅ Application layer depends ONLY on:
   - Domain layer (Entities, VOs, Exceptions, etc.)
   - Ports (Interfaces)
   
❌ Application layer MUST NOT depend on:
   - Infrastructure (Laravel, MySQL, AWS, etc.)
   - Concrete implementations
```

**Port Naming Convention:**
| Type | Format | Example |
|------|--------|---------|
| InPort | `I{UseCaseName}InPort` | `IGenerarSolicitudInPort` |
| OutPort | `I{Concept}OutPort` | `ISolicitudOutPort`, `INotificacionOutPort` |
