**Template:** Use [templates/value-object.php](../templates/value-object.php) as a starting structure.

```php
// filepath: app/Core/Programa/Domain/Vo/CurpVO.php
class CurpVO
{
    private string $valor;
    
    public function __construct(string $curp)
    {
        if (!$this->esFormatoValido($curp)) {
            throw new CurpInvalidaException($curp);
        }
        $this->valor = strtoupper($curp);
    }
    
    private function esFormatoValido(string $curp): bool
    {
        return preg_match('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/', $curp);
    }
    
    public function valor(): string
    {
        return $this->valor;
    }
    
    public function equals(CurpVO $otra): bool
    {
        return $this->valor === $otra->valor();
    }
}
```