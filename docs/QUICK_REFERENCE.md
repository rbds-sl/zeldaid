# Guía Rápida - API de Wallet Passes

Referencia rápida para desarrolladores.

## Headers requeridos

```
Authorization: ApplePass {authToken}
Content-Type: application/json
```

## Endpoints resumen

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| **POST** | `/api/v1/passes` | Crear nuevo pass |
| **PUT** | `/api/v1/passes/{passType}/{serial}` | Actualizar pass |
| **GET** | `/api/v1/passes/{passType}/{serial}` | Descargar .pkpass |
| **POST** | `/api/v1/devices/{deviceId}/registrations/{passType}/{serial}` | Registrar dispositivo |
| **DELETE** | `/api/v1/devices/{deviceId}/registrations/{passType}/{serial}` | Desregistrar dispositivo |
| **GET** | `/api/v1/devices/{deviceId}/registrations/{passType}?lastUpdated={ts}` | Consultar actualizaciones |
| **POST** | `/api/v1/log` | Registrar logs (sin auth) |

## Flujo básico

### 1️⃣ Crear un pass

```bash
curl -X POST http://localhost:8000/api/v1/passes \
  -H "Authorization: ApplePass token" \
  -d '{
    "pass_type_identifier": "pass.com.example",
    "serial_number": "pass-001",
    "template_type": "generic",
    "data": {
      "description": "Mi pass",
      "organizationName": "Mi Empresa"
    }
  }'
```

### 2️⃣ Descargar pass

```bash
curl -X GET http://localhost:8000/api/v1/passes/pass.com.example/pass-001 \
  -H "Authorization: ApplePass token" \
  -o pass.pkpass
```

### 3️⃣ Registrar dispositivo

```bash
curl -X POST http://localhost:8000/api/v1/devices/device-id/registrations/pass.com.example/pass-001 \
  -H "Authorization: ApplePass token" \
  -d '{
    "pushToken": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
  }'
```

### 4️⃣ Actualizar pass

```bash
curl -X PUT http://localhost:8000/api/v1/passes/pass.com.example/pass-001 \
  -H "Authorization: ApplePass token" \
  -d '{
    "data": {
      "description": "Pass actualizado"
    }
  }'
```

### 5️⃣ Consultar cambios

```bash
curl -X GET "http://localhost:8000/api/v1/devices/device-id/registrations/pass.com.example?lastUpdated=1702929600" \
  -H "Authorization: ApplePass token"
```

## Códigos de respuesta

| Código | Significado |
|--------|-------------|
| 200 | ✅ OK - Operación exitosa |
| 201 | ✅ Created - Recurso creado |
| 400 | ❌ Bad Request - Datos inválidos |
| 401 | ❌ Unauthorized - Auth requerida |
| 404 | ❌ Not Found - No existe |

## Validaciones

- **Push Token**: 64 caracteres hexadecimales (0-9, a-f)
- **Pass Type**: Formato válido (ej: `pass.com.example.wallet`)
- **Serial Number**: Único por tipo
- **Auth Token**: Formato "ApplePass {token}"

## Ejemplos de datos

### Pass genérico

```json
{
  "pass_type_identifier": "pass.com.loyalty.card",
  "serial_number": "member-12345",
  "template_type": "generic",
  "data": {
    "description": "Tarjeta de fidelización",
    "organizationName": "Mi Tienda",
    "foregroundColor": "rgb(255, 255, 255)",
    "backgroundColor": "rgb(0, 0, 0)",
    "points": "1250"
  }
}
```

### Pass de evento

```json
{
  "pass_type_identifier": "pass.com.event.ticket",
  "serial_number": "ticket-2025-001",
  "template_type": "eventTicket",
  "data": {
    "description": "Entrada - Concierto",
    "organizationName": "Ticketing Co",
    "venue": "Estadio Nacional",
    "date": "2025-12-25T20:00:00Z",
    "seat": "A-123"
  }
}
```

### Pass de descuento

```json
{
  "pass_type_identifier": "pass.com.coupon",
  "serial_number": "coupon-25-percent",
  "template_type": "coupon",
  "data": {
    "description": "25% de descuento",
    "organizationName": "Mi Tienda",
    "discount": "25%",
    "expiryDate": "2026-12-31T23:59:59Z"
  }
}
```

## Testing

```bash
# Ejecutar todos los tests
docker exec zeldaid-crmservice.local.test-1 php artisan test tests/Feature/WalletPassApiTest.php

# Un test específico
docker exec zeldaid-crmservice.local.test-1 php artisan test tests/Feature/WalletPassApiTest.php::test_register_device_successfully
```

## Logs y debugging

```bash
# Ver logs de errores
docker exec zeldaid-crmservice.local.test-1 tail -f storage/logs/laravel.log

# Consultar logs en BD
SELECT * FROM wallet_pass_logs 
WHERE device_library_identifier = 'device-id' 
ORDER BY created_at DESC;
```

## Errores comunes

### "Unauthorized"
- ✅ Verificar header `Authorization: ApplePass {token}`
- ✅ Verificar formato con espacio entre ApplePass y token

### "Invalid push token"
- ✅ Token debe ser 64 caracteres hexadecimales
- ✅ Verificar formato: `[0-9a-f]{64}`

### "Pass not found"
- ✅ Verificar passTypeIdentifier correcto
- ✅ Verificar serialNumber correcto
- ✅ ¿Se creó el pass primero?

## Recursos

- 📚 [Documentación completa](./API_WALLET_PASSES.md)
- 🧪 [Tests](../tests/Feature/WalletPassApiTest.php)
- 🛠️ [Controlador](../app/Http/Controllers/WalletPassController.php)
- 📦 [Rutas API](../routes/api.php)

---

**Tip:** Usar Postman o Insomnia para probar los endpoints antes de integrar en código.
