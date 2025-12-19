# API de Apple Wallet Passes

Documentación completa del servicio web para gestión de Apple Wallet Passes según la especificación de Apple.

## Tabla de contenidos

1. [Introducción](#introducción)
2. [Autenticación](#autenticación)
3. [Endpoints](#endpoints)
4. [Modelos de datos](#modelos-de-datos)
5. [Flujo de uso](#flujo-de-uso)
6. [Ejemplos prácticos](#ejemplos-prácticos)
7. [Manejo de errores](#manejo-de-errores)
8. [Testing](#testing)

---

## Introducción

Este API implementa el servicio web para Apple Wallet Passes siguiendo la especificación oficial de Apple. Permite:

- **Registrar dispositivos** para recibir notificaciones push de actualizaciones
- **Desregistrar dispositivos** cuando dejan de ser necesarias las notificaciones
- **Obtener passes actualizados** según cambios en la base de datos
- **Descargar archivos .pkpass** en formato binario
- **Crear y actualizar passes** en la base de datos
- **Registrar errores** reportados desde dispositivos Apple

### Especificación de Apple

El API sigue la arquitectura web service documentada en:
- [Apple Wallet Web Service Reference](https://developer.apple.com/library/archive/documentation/PassKit/Conceptual/PassKit_ProgrammingGuide/WebService.html)

### Tecnologías

- **Framework**: Laravel 11 (PHP 8.4)
- **Base de datos**: PostgreSQL 16
- **Generador de passes**: byte5/laravel-passgenerator
- **Certificados**: Apple WWDR + Certificado firmante

---

## Autenticación

Todos los endpoints (excepto `/log`) requieren autenticación Bearer Token en el header:

```
Authorization: ApplePass {authToken}
```

El token debe seguir el formato `ApplePass {token}`. El servidor valida que exista un espacio entre "ApplePass" y el token.

### Ejemplo de header correcto

```http
Authorization: ApplePass my-secret-token-12345
```

### Respuesta sin autenticación

```json
{
  "error": "Unauthorized"
}
```

HTTP Status: `401 Unauthorized`

---

## Endpoints

### 1. Registrar dispositivo para notificaciones

**Endpoint:**
```
POST /api/v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}
```

**Descripción:**
Registra un dispositivo para recibir notificaciones push cuando el pass se actualiza.

**Parámetros de ruta:**
- `deviceLibraryIdentifier` (string): ID único del dispositivo
- `passTypeIdentifier` (string): Tipo de pass (ej: `pass.com.example.wallet`)
- `serialNumber` (string): Número serial único del pass

**Body (JSON):**
```json
{
  "pushToken": "a1b2c3d4e5f6...f6e5d4c3b2a1" // 64 caracteres hexadecimales
}
```

**Headers:**
```
Authorization: ApplePass {authToken}
Content-Type: application/json
```

**Respuestas exitosas:**

- **201 Created**: Dispositivo registrado o actualizado correctamente
  ```json
  {
    "message": "Device registered successfully"
  }
  ```

**Respuestas de error:**

- **400 Bad Request**: Token de push inválido
  ```json
  {
    "error": "Invalid push token"
  }
  ```

- **401 Unauthorized**: Header de autorización faltante o inválido
  ```json
  {
    "error": "Unauthorized"
  }
  ```

**Validaciones:**
- El token de push debe ser exactamente 64 caracteres hexadecimales (0-9, a-f)
- Si el dispositivo ya está registrado, se actualiza el push token
- Se registra la fecha de actualización (`last_updated_at`)

---

### 2. Desregistrar dispositivo

**Endpoint:**
```
DELETE /api/v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}
```

**Descripción:**
Elimina el registro de un dispositivo, dejando de enviar notificaciones push.

**Parámetros de ruta:**
- `deviceLibraryIdentifier` (string): ID único del dispositivo
- `passTypeIdentifier` (string): Tipo de pass
- `serialNumber` (string): Número serial del pass

**Headers:**
```
Authorization: ApplePass {authToken}
```

**Respuestas exitosas:**

- **200 OK**: Dispositivo desregistrado
  ```json
  {
    "message": "Device unregistered successfully"
  }
  ```

**Respuestas de error:**

- **401 Unauthorized**: Autenticación requerida
- **404 Not Found**: Registro no encontrado (se devuelve 200 por compatibilidad con Apple)

---

### 3. Obtener passes actualizados

**Endpoint:**
```
GET /api/v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}?lastUpdated={timestamp}
```

**Descripción:**
Retorna la lista de números seriales de passes que han sido actualizados después de `lastUpdated`.

**Parámetros de ruta:**
- `deviceLibraryIdentifier` (string): ID único del dispositivo
- `passTypeIdentifier` (string): Tipo de pass

**Parámetros de query:**
- `lastUpdated` (integer): Timestamp UNIX de la última actualización conocida

**Headers:**
```
Authorization: ApplePass {authToken}
```

**Respuesta exitosa (200 OK):**
```json
{
  "lastUpdated": 1702929600,
  "serialNumbers": [
    "pass-123-updated",
    "pass-456-updated"
  ]
}
```

**Campos:**
- `lastUpdated`: Timestamp más reciente de actualización de los passes
- `serialNumbers`: Array de números seriales de passes actualizados

**Si no hay cambios:**
```json
{
  "lastUpdated": 1702929600,
  "serialNumbers": []
}
```

---

### 4. Obtener archivo .pkpass

**Endpoint:**
```
GET /api/v1/passes/{passTypeIdentifier}/{serialNumber}
```

**Descripción:**
Descarga el archivo binario del pass (.pkpass) que puede ser agregado directamente a Apple Wallet.

**Parámetros de ruta:**
- `passTypeIdentifier` (string): Tipo de pass
- `serialNumber` (string): Número serial del pass

**Headers:**
```
Authorization: ApplePass {authToken}
```

**Respuesta exitosa (200 OK):**
```
Content-Type: application/vnd.apple.pkpass
Content-Disposition: attachment; filename="pass.pkpass"

[Contenido binario del archivo .pkpass]
```

**Respuestas de error:**

- **404 Not Found**: Pass no encontrado
  ```
  HTTP/1.1 404 Not Found
  ```

**Notas:**
- El cliente debe guardar el archivo binario como `.pkpass`
- El archivo está firmado digitalmente con el certificado Apple
- Incluye todos los assets (imágenes, datos de configuración, etc.)

---

### 5. Registrar errores desde dispositivo

**Endpoint:**
```
POST /api/v1/log
```

**Descripción:**
Registra mensajes de error y log reportados por dispositivos Apple.

**Sin autenticación requerida** (endpoint público)

**Body (JSON):**
```json
{
  "deviceLibraryIdentifier": "device-id-12345",
  "logs": [
    {
      "message": "Error al agregar el pass",
      "passTypeIdentifier": "pass.com.example.wallet",
      "serialNumber": "pass-123"
    },
    {
      "message": "Actualización completada",
      "passTypeIdentifier": "pass.com.example.wallet"
    }
  ]
}
```

**Body Parameters:**
- `deviceLibraryIdentifier` (string): ID del dispositivo reportando el error
- `logs` (array): Lista de entradas de log
  - `message` (string): Mensaje de error/log
  - `passTypeIdentifier` (string, optional): Tipo de pass asociado
  - `serialNumber` (string, optional): Número serial del pass

**Respuesta exitosa (200 OK):**
```json
{
  "message": "Logs recorded successfully"
}
```

**Almacenamiento:**
- Los logs se guardan en la tabla `wallet_pass_logs`
- Incluyen timestamp automático
- Se clasifican con `log_level: 'error'`
- Asociados al dispositivo y pass si se proporcionan

---

### 6. Crear nuevo pass

**Endpoint:**
```
POST /api/v1/passes
```

**Descripción:**
Crea un nuevo pass en la base de datos que puede ser descargado posteriormente.

**Headers:**
```
Authorization: ApplePass {authToken}
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "pass_type_identifier": "pass.com.example.wallet",
  "serial_number": "pass-001",
  "template_type": "generic",
  "data": {
    "description": "Mi primer pass",
    "organizationName": "Ejemplo Inc.",
    "foregroundColor": "rgb(255, 0, 0)",
    "backgroundColor": "rgb(255, 255, 255)",
    "logo": {
      "url": "https://example.com/logo.png"
    }
  }
}
```

**Body Parameters:**
- `pass_type_identifier` (string): Identificador único del tipo de pass
- `serial_number` (string): Identificador único del pass
- `template_type` (string): Tipo de plantilla (generic, coupon, eventTicket, etc.)
- `data` (object): Configuración del pass (JSON flexible)
  - `description` (string): Descripción del pass
  - `organizationName` (string): Nombre de la organización
  - `foregroundColor` (string, opcional): Color en formato rgb()
  - `backgroundColor` (string, opcional): Color en formato rgb()
  - `webServiceURL` (string, opcional): URL del web service para registro de dispositivos
    - Si no se proporciona, se usa automáticamente: `{APP_URL}/api/v1`
    - Esta URL es necesaria para que Apple Wallet registre dispositivos

**Respuesta exitosa (201 Created):**
```json
{
  "id": 42,
  "pass_type_identifier": "pass.com.example.wallet",
  "serial_number": "pass-001",
  "template_type": "generic",
  "created_at": "2025-12-18T10:30:00Z",
  "updated_at": "2025-12-18T10:30:00Z"
}
```

**Respuestas de error:**

- **400 Bad Request**: Datos incompletos o inválidos
- **401 Unauthorized**: Autenticación requerida
- **409 Conflict**: Pass ya existe (crear otro con serial_number diferente)

---

### 7. Actualizar pass existente

**Endpoint:**
```
PUT /api/v1/passes/{passTypeIdentifier}/{serialNumber}
```

**Descripción:**
Actualiza los datos de un pass existente. Se fusionan los cambios con los datos existentes.

**Parámetros de ruta:**
- `passTypeIdentifier` (string): Tipo de pass
- `serialNumber` (string): Número serial del pass

**Headers:**
```
Authorization: ApplePass {authToken}
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "data": {
    "description": "Pass actualizado",
    "foregroundColor": "rgb(0, 0, 255)",
    "newField": "nuevo valor"
  }
}
```

**Funcionamiento:**
- Obtiene el pass existente
- Fusiona los nuevos datos con los existentes (merge recursivo)
- Actualiza `updated_at` automáticamente
- Notifica a dispositivos registrados sobre la actualización

**Respuesta exitosa (200 OK):**
```json
{
  "message": "Pass updated successfully",
  "id": 42,
  "pass_type_identifier": "pass.com.example.wallet",
  "serial_number": "pass-001"
}
```

**Respuestas de error:**

- **404 Not Found**: Pass no encontrado
  ```json
  {
    "error": "Pass not found"
  }
  ```

- **401 Unauthorized**: Autenticación requerida

**Notificaciones Push:**
Después de actualizar, se envían notificaciones push a todos los dispositivos registrados para este pass.

---

## Modelos de datos

### WalletPass

Almacena información de los passes.

```php
{
  "id": 1,
  "pass_type_identifier": "pass.com.example.wallet",
  "serial_number": "pass-001",
  "template_type": "generic",
  "data": { /* JSON con configuración del pass */ },
  "created_at": "2025-12-18T10:30:00Z",
  "updated_at": "2025-12-18T10:30:00Z"
}
```

**Tabla:** `wallet_passes`

**Indices:**
- `pass_type_identifier, serial_number` (unique)
- `updated_at` (para consultas de actualizaciones)

---

### WalletPassRegistration

Registra qué dispositivos están suscritos a actualizaciones de cada pass.

```php
{
  "id": 1,
  "device_library_identifier": "device-id-12345",
  "pass_type_identifier": "pass.com.example.wallet",
  "serial_number": "pass-001",
  "push_token": "a1b2c3d4e5f6...f6e5d4c3b2a1",
  "registered_at": "2025-12-18T10:30:00Z",
  "last_updated_at": "2025-12-18T10:35:00Z"
}
```

**Tabla:** `wallet_pass_registrations`

**Indices:**
- `device_library_identifier, pass_type_identifier, serial_number` (unique)
- `last_updated_at` (para queries de cambios)

---

### WalletPassLog

Almacena logs y errores reportados por dispositivos.

```php
{
  "id": 1,
  "device_library_identifier": "device-id-12345",
  "pass_type_identifier": "pass.com.example.wallet",
  "serial_number": "pass-001",
  "message": "Error al actualizar el pass",
  "log_level": "error",
  "created_at": "2025-12-18T10:30:00Z"
}
```

**Tabla:** `wallet_pass_logs`

**Campos:**
- `log_level`: "error" o "info"
- Los campos `pass_type_identifier` y `serial_number` son opcionales

---

## Flujo de uso

### Flujo típico de un usuario

```
1. Cliente crea un pass
   POST /api/v1/passes
   ↓
2. Usuario agrega el pass a Apple Wallet
   GET /api/v1/passes/{passTypeIdentifier}/{serialNumber}
   ↓
3. Dispositivo se registra para notificaciones
   POST /api/v1/devices/{deviceLibraryIdentifier}/registrations/...
   ↓
4. (Cuando el pass cambia)
   PUT /api/v1/passes/{passTypeIdentifier}/{serialNumber}
   ↓
5. Sistema notifica a dispositivos registrados
   (push notification automática)
   ↓
6. Dispositivo consulta si hay cambios
   GET /api/v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}?lastUpdated=...
   ↓
7. Si hay cambios, descarga el pass actualizado
   GET /api/v1/passes/{passTypeIdentifier}/{serialNumber}
```

### Secuencia de autenticación

1. Cliente debe tener un `authToken` válido
2. Incluir en cada request: `Authorization: ApplePass {authToken}`
3. Servidor valida el formato del header
4. Si es válido, procesa la solicitud
5. Si es inválido, devuelve 401 Unauthorized

---

## Ejemplos prácticos

### Ejemplo 1: Crear un pass de evento

```bash
curl -X POST http://localhost:8000/api/v1/passes \
  -H "Authorization: ApplePass my-token-123" \
  -H "Content-Type: application/json" \
  -d '{
    "pass_type_identifier": "pass.com.ticketing.event",
    "serial_number": "ticket-2025-001",
    "template_type": "eventTicket",
    "data": {
      "description": "Concierto - Coldplay",
      "organizationName": "Ticketing Corp",
      "foregroundColor": "rgb(255, 255, 255)",
      "backgroundColor": "rgb(33, 33, 33)",
      "venue": "Estadio Santiago Bernabéu",
      "date": "2025-12-25T20:00:00Z",
      "seat": "A-123"
    }
  }'
```

**Respuesta:**
```json
{
  "id": 1,
  "pass_type_identifier": "pass.com.ticketing.event",
  "serial_number": "ticket-2025-001",
  "template_type": "eventTicket",
  "created_at": "2025-12-18T10:30:00Z",
  "updated_at": "2025-12-18T10:30:00Z"
}
```

---

### Ejemplo 2: Registrar dispositivo para notificaciones

```bash
curl -X POST http://localhost:8000/api/v1/devices/device-iphone-12345/registrations/pass.com.ticketing.event/ticket-2025-001 \
  -H "Authorization: ApplePass my-token-123" \
  -H "Content-Type: application/json" \
  -d '{
    "pushToken": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
  }'
```

**Respuesta:**
```json
{
  "message": "Device registered successfully"
}
```

---

### Ejemplo 3: Descargar el pass

```bash
curl -X GET http://localhost:8000/api/v1/passes/pass.com.ticketing.event/ticket-2025-001 \
  -H "Authorization: ApplePass my-token-123" \
  -o ticket.pkpass
```

El archivo `ticket.pkpass` se guarda localmente y puede ser abierto en Apple Wallet.

---

### Ejemplo 4: Actualizar el pass

```bash
curl -X PUT http://localhost:8000/api/v1/passes/pass.com.ticketing.event/ticket-2025-001 \
  -H "Authorization: ApplePass my-token-123" \
  -H "Content-Type: application/json" \
  -d '{
    "data": {
      "seat": "A-125",
      "date": "2025-12-26T20:00:00Z"
    }
  }'
```

**Respuesta:**
```json
{
  "message": "Pass updated successfully",
  "id": 1,
  "pass_type_identifier": "pass.com.ticketing.event",
  "serial_number": "ticket-2025-001"
}
```

Todos los dispositivos registrados recibirán una notificación push.

---

### Ejemplo 5: Consultar passes actualizados

```bash
curl -X GET "http://localhost:8000/api/v1/devices/device-iphone-12345/registrations/pass.com.ticketing.event?lastUpdated=1702929600" \
  -H "Authorization: ApplePass my-token-123"
```

**Respuesta:**
```json
{
  "lastUpdated": 1703015600,
  "serialNumbers": ["ticket-2025-001"]
}
```

El dispositivo sabrá que debe descargar la versión actualizada del pass.

---

### Ejemplo 6: Registrar logs desde dispositivo

```bash
curl -X POST http://localhost:8000/api/v1/log \
  -H "Content-Type: application/json" \
  -d '{
    "deviceLibraryIdentifier": "device-iphone-12345",
    "logs": [
      {
        "message": "Pass agregado a Wallet correctamente",
        "passTypeIdentifier": "pass.com.ticketing.event",
        "serialNumber": "ticket-2025-001"
      },
      {
        "message": "Actualización recibida correctamente",
        "passTypeIdentifier": "pass.com.ticketing.event",
        "serialNumber": "ticket-2025-001"
      }
    ]
  }'
```

**Respuesta:**
```json
{
  "message": "Logs recorded successfully"
}
```

---

## Manejo de errores

### Códigos de estado HTTP

| Código | Descripción | Ejemplo |
|--------|-------------|---------|
| 200 | OK - Solicitud exitosa | GET pass, DELETE registro |
| 201 | Created - Recurso creado | POST pass |
| 400 | Bad Request - Datos inválidos | Token push inválido |
| 401 | Unauthorized - Autenticación requerida | Header faltante |
| 404 | Not Found - Recurso no encontrado | Pass inexistente |
| 409 | Conflict - Duplicado | Pass ya existe |

### Estructura de errores

```json
{
  "error": "Descripción del error"
}
```

### Ejemplos de errores comunes

**Push token inválido:**
```json
{
  "error": "Invalid push token"
}
```

**Autenticación faltante:**
```json
{
  "error": "Unauthorized"
}
```

**Pass no encontrado:**
```json
{
  "error": "Pass not found"
}
```

### Validaciones

- **Push token**: Exactamente 64 caracteres hexadecimales (0-9, a-f)
- **Pass type identifier**: Formato válido (ej: `pass.com.example.wallet`)
- **Serial number**: Único por tipo de pass
- **Timestamp**: Formato UNIX timestamp (segundos desde época)

---

## Testing

### Ejecutar suite de tests

```bash
docker exec zeldaid-crmservice.local.test-1 php artisan test tests/Feature/WalletPassApiTest.php
```

### Tests incluidos

- ✅ Registrar dispositivo exitosamente
- ✅ Registrar dispositivo sin autenticación
- ✅ Registrar dispositivo con push token inválido
- ✅ Desregistrar dispositivo
- ✅ Obtener passes actualizados
- ✅ Crear nuevo pass
- ✅ Actualizar pass existente
- ✅ Actualizar pass inexistente
- ✅ Descargar archivo .pkpass
- ✅ Descargar pass inexistente
- ✅ Registrar logs de error
- ✅ Actualizar registro con nuevo token
- ✅ Flujo completo (crear, registrar, actualizar, consultar)

### Ejecutar test específico

```bash
docker exec zeldaid-crmservice.local.test-1 php artisan test tests/Feature/WalletPassApiTest.php::test_register_device_successfully
```

### Coverage

```bash
docker exec zeldaid-crmservice.local.test-1 php artisan test tests/Feature/WalletPassApiTest.php --coverage
```

---

## Notas de implementación

### Certificados

- Se requiere certificado WWDR de Apple
- Se requiere certificado firmante personal
- Los certificados deben colocarse en: `storage/app/private/passgenerator/certs/`
- Configurados en `.env`:
  - `PASSGENERATOR_CERTIFICATE_PATH`: Ruta al certificado .p12
  - `PASSGENERATOR_PASSWORD`: Contraseña del certificado

### Configuración

```env
# Wallet Passes
PASS_TYPE_IDENTIFIER=pass.com.example.wallet
TEAM_IDENTIFIER=H7TVGT2YV3
PASSGENERATOR_CERTIFICATE_PATH=passgenerator/certs/certificate-sign-wallet-apple.p12
PASSGENERATOR_PASSWORD=your_certificate_password
```

### Notificaciones push

- Actualmente implementada con simulación (logging)
- Para producción, integrar con Apple Push Notification service (APNs)
- Se requiere certificado .p8 de Apple Developer

### Performance

- Los índices en las tablas optimizan queries de actualización
- Los timestamps `updated_at` permiten consultas eficientes
- Se recomienda archivado periódico de logs antiguos

---

## Soporte y resolución de problemas

### Certificado no encontrado

```
Error: No certificate found
```

**Solución:**
- Verificar ruta en `.env`
- Usar caminos relativos a `storage/app/private/`
- Ejecutar: `php artisan config:clear && php artisan cache:clear`

### Token de push inválido

```json
{
  "error": "Invalid push token"
}
```

**Solución:**
- Token debe tener exactamente 64 caracteres
- Debe ser hexadecimal (0-9, a-f)
- Obtener del dispositivo Apple correctamente

### Pass no se actualiza

**Checklist:**
1. ¿Se ejecutó `PUT /api/v1/passes/...`?
2. ¿El dispositivo está registrado?
3. ¿Se consultó `GET /api/v1/devices/.../registrations/...?lastUpdated=...`?
4. ¿Se descargó el nuevo pass?

---

**Versión:** 1.0  
**Última actualización:** 18 de diciembre de 2025  
**Especificación:** Apple Wallet Web Service v1.0
