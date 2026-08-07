**Template:** Use [templates/aggregate.php](../templates/aggregate.php) as a starting structure.

```php
// filepath: app/Core/Programa/Domain/Aggregates/SolicitudBeneficioAggregate.php
class SolicitudBeneficioAggregate
{
    private int $id; // Aggregate Root ID
    private SolicitudEntity $solicitud;
    private array $documentosAdjuntos; // DocumentoAdjuntoEntity[]
    private PeriodoVigenciaVO $periodoVigencia;
    private MontoBeneficioVO $montoBeneficio;
    private EstatusSolicitudEnum $estatus;
    
    // All modifications go through Aggregate Root
    public function adjuntarDocumento(DocumentoAdjuntoEntity $doc): void
    {
        $this->documentosAdjuntos[] = $doc;
    }
}
```