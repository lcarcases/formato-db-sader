**Formato 1: Descripción Simple**
```
"Generar solicitud de beneficio para un productor"
```

**Formato 2: Descripción con Contexto**
```
Caso de uso: Generar solicitud de beneficio para un productor
Módulo: Programa
Actor: Usuario del sistema
Entrada: API REST (POST /api/solicitudes)
```

**Formato 3: Descripción Detallada (Recomendado)**
```
Caso de uso: Generar solicitud de beneficio para un productor

Módulo: Programa

Actor: Usuario del sistema (funcionario)

Descripción:
El sistema debe permitir crear una nueva solicitud de beneficio 
para un productor agrícola registrado en el padrón.

Datos de entrada:
- CURP del productor (obligatorio)
- Clave del programa (obligatorio)
- Año fiscal (obligatorio)
- Estado (obligatorio)
- Monto solicitado (opcional)

Reglas de negocio:
1. El productor debe existir en el padrón
2. El productor debe estar activo (no fallecido)
3. El programa debe estar vigente
4. No debe existir una solicitud duplicada para el mismo año
5. El folio debe generarse con formato: AA-PROGRAMA-XXXX-NNNNNN-LNNN-EE

Sistemas externos:
- MySQL (persistencia)
- Servicio de notificaciones (envío de correo de confirmación)

Tipo de entrada: API REST (POST /api/v1/solicitudes)

Respuesta esperada:
- ID de la solicitud creada
- Folio generado
- Estatus inicial
- Mensaje de confirmación
```

**Formato 4: Historia de Usuario**
```
COMO funcionario del programa
QUIERO generar una solicitud de beneficio para un productor
PARA que pueda recibir los apoyos del programa

Criterios de aceptación:
- Dado un productor activo en el padrón
- Cuando genero una solicitud con datos válidos
- Entonces se crea la solicitud con un folio único

- Dado un productor que no existe
- Cuando intento generar una solicitud
- Entonces recibo un error indicando que no existe

- Dado un productor fallecido
- Cuando intento generar una solicitud
- Entonces recibo un error indicando que no está activo
```