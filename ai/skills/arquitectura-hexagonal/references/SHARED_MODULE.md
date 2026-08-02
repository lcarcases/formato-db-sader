## Shared Module (Core/Shared)

### Purpose

The **Shared** module contains **truly reusable** code used across multiple bounded contexts (modules) **without any modification**. It prevents coupling between modules while avoiding unnecessary duplication.

```
app/Core/Shared/
├── Domain/
│   ├── Vo/              # Reusable Value Objects
│   ├── Exceptions/      # Base domain exceptions
│   
├── Application/
│   ├── Dto/             # IDto interface
│   └── Services/        # Cross-cutting application services
└── Infrastructure/
    └── Helpers/         # Framework utilities
```

---

### ✅ What SHOULD go in Shared

| Category | Examples | Reasoning |
|----------|----------|-----------|
| **Generic Value Objects** | `EmailVO`, `MoneyVO`, `UrlVO`, `PhoneVO` | Used identically across modules |
| **Base Exceptions** | `DomainException`, `ValidationException` | Standard exception hierarchy |
| **DTO Contracts** | `IDto` interface | All DTOs implement same contract |
| **Common Specifications** | `ISpecification<T>` interface | Generic pattern interface |
| **Date/Time utilities** | `DateRangeVO`, `TimestampVO` | Universal date handling |
| **Measurement VOs** | `PercentageVO`, `QuantityVO` | Standard measurements |

---

### ❌ What should NOT go in Shared

| Anti-Pattern | Why |
|--------------|-----|
| Domain-specific VOs (e.g., `FolioVO`) | Only relevant to specific modules |
| Business logic from a single module | Creates unnecessary coupling |
| "Might be reused someday" code | Wait until actual reuse happens |
| Code modified per module | If you change it per module, it's not shared |
| Infrastructure adapters (MySQL, AWS) | Module-specific implementations |

---

### Decision Rule: When to Promote to Shared

```
RULE: Copy same code to 3+ modules WITHOUT any changes
      → THEN consider promoting to Shared

NEVER: Start in Shared "just in case"
       Promote code that changes per module
       Modify Shared code without checking ALL consumers
```

**Process:**

1. **Write code in Module A** (e.g., `EmailVO` in `Programa` module)
2. **Need same code in Module B** → Copy it to Module B
3. **Need same code in Module C** → Copy it to Module C
4. **Verify**: Is the code **identical** in all 3 modules?
   - ✅ YES → Promote to `Core/Shared`
   - ❌ NO → Keep duplicated; it's not truly shared

**After promotion:**
```php
// Before: app/Core/Programa/Domain/Vo/EmailVO.php
// Before: app/Core/Beneficiario/Domain/Vo/EmailVO.php
// Before: app/Core/Solicitud/Domain/Vo/EmailVO.php

// After: app/Core/Shared/Domain/Vo/EmailVO.php
```

---

### Example: EmailVO (Shared Value Object)

```php
// filepath: app/Core/Shared/Domain/Vo/EmailVO.php
<?php

namespace App\Core\Shared\Domain\Vo;

use App\Core\Shared\Domain\Exceptions\InvalidEmailException;

final class EmailVO
{
    private string $value;

    public function __construct(string $email)
    {
        $email = strtolower(trim($email));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Email '{$email}' no es válido");
        }
        
        $this->value = $email;
    }

    public function valor(): string
    {
        return $this->value;
    }

    public function equals(EmailVO $other): bool
    {
        return $this->value === $other->value;
    }
}
```

**Usage across modules:**
```php
// In Programa module
use App\Core\Shared\Domain\Vo\EmailVO;
$email = new EmailVO($dto->email);

// In Beneficiario module (identical usage)
use App\Core\Shared\Domain\Vo\EmailVO;
$email = new EmailVO($dto->email);
```

---

### Example: IDto Interface

```php
// filepath: app/Core/Shared/Application/Dto/IDto.php
<?php

namespace App\Core\Shared\Application\Dto;

interface IDto
{
    // Marker interface - all DTOs implement this
}
```

**Used by all modules:**
```php
// app/Core/Programa/Application/Dtos/In/GenerarSolicitudInDto.php
use App\Core\Shared\Application\Dto\IDto;

class GenerarSolicitudInDto implements IDto { }
```

---

### ⚠️ Warning: Changing Shared Code

**Before modifying anything in Shared:**

1. **Search ALL usages** across the entire codebase
2. **Verify impact** on EVERY consuming module
3. **Run ALL tests** to detect regressions
4. **Consider versioning** if breaking changes needed

```bash
# Find all usages of EmailVO
grep -r "use App\\Core\\Shared\\Domain\\Vo\\EmailVO" app/Core/
```

**If a module needs different behavior:**
→ Do NOT modify Shared code
→ Create a module-specific version instead

---

### Common Shared Components

**Domain Layer:**
```
Shared/Domain/
├── Vo/
│   ├── EmailVO.php
│   ├── MoneyVO.php
│   ├── UrlVO.php
│   ├── PhoneVO.php
│   ├── PercentageVO.php
│   └── DateRangeVO.php
├── Exceptions/
│   ├── DomainException.php
│   ├── ValidationException.php
│   └── NotFoundException.php
└── Contracts/
    └── ISpecification.php
```

**Application Layer:**
```
Shared/Application/
├── Dto/
│   └── IDto.php
└── Services/
    └── (rarely used - most services are module-specific)
```

**Infrastructure Layer:**
```
Shared/Infrastructure/
├── Helpers/
│   └── Respuesta.php  # Standard JSON response formatter
└── Formatters/
    └── DateFormatter.php
```

---

### Summary Checklist

Before promoting code to Shared:

```
☐ Code is used in 3+ modules
☐ Code is IDENTICAL in all modules (no modifications)
☐ Code is truly generic (not domain-specific)
☐ Changing it would affect all consumers equally
☐ All tests pass after moving to Shared
```

If any checkbox is unchecked → Keep code duplicated in each module.