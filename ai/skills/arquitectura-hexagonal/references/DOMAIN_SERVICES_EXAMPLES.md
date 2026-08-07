## 3.7 Create Domain Services (cross-entity logic)

**Template:** Use [templates/domain-service.php](../templates/domain-service.php) as a starting structure.

```php
// filepath: app/Core/Programa/Domain/Services/ElegibilidadBeneficiarioDomainService.php
class ElegibilidadBeneficiarioDomainService
{
    public function esElegible(
        BeneficiarioEntity $beneficiario,
        ProgramaEntity $programa,
        array $solicitudesExistentes
    ): bool {
        // Logic that spans multiple entities
        $cumpleEdad = $beneficiario->getEdad() >= $programa->getEdadMinima();
        $cumpleSuperficie = $beneficiario->getSuperficie() <= $programa->getSuperficieMaxima();
        $sinDuplicidad = $this->verificarNoDuplicidad($beneficiario, $solicitudesExistentes);
        
        return $cumpleEdad && $cumpleSuperficie && $sinDuplicidad;
    }
}
```
