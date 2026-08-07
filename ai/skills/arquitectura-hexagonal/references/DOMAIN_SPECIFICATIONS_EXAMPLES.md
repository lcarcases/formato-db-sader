## 3.6 Create Specifications (boolean rules)

**Template:** Use [templates/specification.php](../templates/specification.php) as a starting structure.

```php
// filepath: app/Core/Programa/Domain/Specifications/SuperficieMaximaSpecification.php
class SuperficieMaximaSpecification implements ISpecification
{
    private float $maximoHectareas;
    
    public function __construct(float $maximoHectareas = 50.0)
    {
        $this->maximoHectareas = $maximoHectareas;
    }
    
    public function isSatisfiedBy(BeneficiarioEntity $beneficiario): bool
    {
        return $beneficiario->getSuperficie() <= $this->maximoHectareas;
    }
}
```
