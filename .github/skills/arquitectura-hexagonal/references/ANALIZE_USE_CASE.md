

- Extraer verbos → acciones (casos de uso potenciales)
- Extraer sustantivos → conceptos de dominio (entidades, objetos de valor)
- Identificar invariantes (reglas de negocio que siempre deben cumplirse)
- Identificar actores (quién activa el caso de uso: Usuario, Sistema, API externa)
- Identificar precondiciones (qué debe cumplirse antes de la ejecución)
- Identificar postcondiciones (qué debe cumplirse después de la ejecución)
- Identificar excepciones (qué puede fallar)

**Ejemplo:**
```
Caso de uso: "Generar solicitud de beneficio para un productor"

Verbos: Generar, Validar, Persistir → Acciones
Sustantivos: Solicitud, Beneficio, Productor, Folio → Conceptos de dominio
Invariantes:

- El folio debe seguir el formato: AA-PRONAFE-XXXX-NNNNNN-LNNN-EE

- El productor debe estar activo (vivo)

- La solicitud no puede tener importes negativos
Actores: Usuario del sistema
Precondiciones: El Productor existe, el Programa está activo
Postcondiciones: Solicitud creada con Folio único
Excepciones: PersonaNoActivaException, ProgramaNoEncontradoException