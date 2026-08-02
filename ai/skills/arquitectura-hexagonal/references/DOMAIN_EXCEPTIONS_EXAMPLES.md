## 3.5 Create Domain Exceptions (business errors)

**Template:** Use [templates/domain-exception.php](../templates/domain-exception.php) as a starting structure.

```php
// filepath: app/Core/Programa/Domain/Exceptions/PersonaNoActivaException.php
class PersonaNoActivaException extends DomainException
{
    public function __construct(string $curp)
    {
        parent::__construct("La persona con CURP {$curp} no está activa");
    }
}
```
