# API de Apple Wallet Passes - Documentación

Documentación completa del servicio web para gestión de Apple Wallet Passes.

## 📚 Documentación disponible

### 1. **[API_WALLET_PASSES.md](./API_WALLET_PASSES.md)** - Documentación completa
Referencia técnica detallada de todos los endpoints:
- Autenticación y headers
- Especificación de cada endpoint
- Modelos de datos
- Flujo de uso
- Ejemplos cURL completos
- Manejo de errores
- Testing

**Para:** Desarrolladores backend, arquitectos

### 2. **[QUICK_REFERENCE.md](./QUICK_REFERENCE.md)** - Guía rápida
Referencia resumida para consultas rápidas:
- Tabla de endpoints
- Flujo básico
- Códigos de respuesta
- Validaciones
- Ejemplos de datos
- Testing rápido
- Errores comunes

**Para:** Desarrollo rápido, debugging

### 3. **[INTEGRATION_GUIDE.md](./INTEGRATION_GUIDE.md)** - Guía de integración
Ejemplos de código para diferentes plataformas:
- iOS/Swift
- Android/Kotlin
- Web/JavaScript (Fetch, Axios)
- PHP/Laravel
- Python (sync y async)
- Postman

**Para:** Desarrolladores frontend, integraciones

---

## 🚀 Inicio rápido

### Crear un pass

```bash
curl -X POST http://localhost:8000/api/v1/passes \
  -H "Authorization: ApplePass my-token" \
  -H "Content-Type: application/json" \
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

### Descargar el pass

```bash
curl -X GET http://localhost:8000/api/v1/passes/pass.com.example/pass-001 \
  -H "Authorization: ApplePass my-token" \
  -o pass.pkpass
```

### Registrar dispositivo

```bash
curl -X POST http://localhost:8000/api/v1/devices/device-id/registrations/pass.com.example/pass-001 \
  -H "Authorization: ApplePass my-token" \
  -H "Content-Type: application/json" \
  -d '{"pushToken": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"}'
```

---

## 📋 Endpoints disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| **POST** | `/api/v1/passes` | Crear nuevo pass |
| **PUT** | `/api/v1/passes/{type}/{serial}` | Actualizar pass |
| **GET** | `/api/v1/passes/{type}/{serial}` | Descargar .pkpass |
| **POST** | `/api/v1/devices/{id}/registrations/{type}/{serial}` | Registrar dispositivo |
| **DELETE** | `/api/v1/devices/{id}/registrations/{type}/{serial}` | Desregistrar |
| **GET** | `/api/v1/devices/{id}/registrations/{type}` | Consultar cambios |
| **POST** | `/api/v1/log` | Registrar logs |

---

## 🔐 Autenticación

Todos los endpoints requieren:

```
Authorization: ApplePass {authToken}
```

**Formato:**
- Literal: "ApplePass"
- Espacio
- Token de autenticación

---

## ✅ Tests

Ejecutar la suite completa:

```bash
docker exec zeldaid-crmservice.local.test-1 php artisan test tests/Feature/WalletPassApiTest.php
```

**Cobertura:** 13 tests
- ✅ Registro de dispositivos
- ✅ Desregistro
- ✅ Actualización de passes
- ✅ Descarga de archivos
- ✅ Logging de errores
- ✅ Flujo completo
- ✅ Validaciones de seguridad

---

## 🛠️ Tecnologías

- **Framework:** Laravel 11 (PHP 8.4)
- **Base de datos:** PostgreSQL 16
- **Generador de passes:** byte5/laravel-passgenerator
- **Certificados:** Apple WWDR + Certificado firmante
- **Testing:** PHPUnit + Laravel Testing helpers

---

## 📦 Estructura de archivos

```
docs/
├── API_WALLET_PASSES.md       # Documentación técnica completa
├── QUICK_REFERENCE.md         # Guía de referencia rápida
├── INTEGRATION_GUIDE.md       # Guía para diferentes plataformas
└── README.md                  # Este archivo

app/Http/Controllers/
└── WalletPassController.php   # Controlador principal (7 endpoints)

app/Services/
├── WalletPushNotificationService.php  # Notificaciones push
└── WalletPassGeneratorService.php     # Generación de .pkpass

app/Models/
├── WalletPass.php             # Modelo de passes
├── WalletPassRegistration.php # Registros de dispositivos
└── WalletPassLog.php          # Logs y errores

routes/
└── api.php                    # Definición de rutas

tests/Feature/
└── WalletPassApiTest.php      # Suite de tests (13 tests)

database/
└── migrations/                # Migraciones de BD
```

---

## 🔧 Configuración

### Variables de entorno (.env)

```env
# Wallet Passes
PASS_TYPE_IDENTIFIER=pass.com.example.wallet
TEAM_IDENTIFIER=H7TVGT2YV3
PASSGENERATOR_CERTIFICATE_PATH=passgenerator/certs/certificate-sign-wallet-apple.p12
PASSGENERATOR_PASSWORD=your_certificate_password
```

### Base de datos

Las tablas se crean automáticamente:
- `wallet_passes` - Almacena los passes
- `wallet_pass_registrations` - Registros de dispositivos
- `wallet_pass_logs` - Logs de errores

---

## 📊 Modelos de datos

### WalletPass
```json
{
  "id": 1,
  "pass_type_identifier": "pass.com.example",
  "serial_number": "pass-001",
  "template_type": "generic",
  "data": { "description": "...", "organizationName": "..." },
  "created_at": "2025-12-18T10:30:00Z",
  "updated_at": "2025-12-18T10:30:00Z"
}
```

### WalletPassRegistration
```json
{
  "id": 1,
  "device_library_identifier": "device-id",
  "pass_type_identifier": "pass.com.example",
  "serial_number": "pass-001",
  "push_token": "aaaa...",
  "registered_at": "2025-12-18T10:30:00Z",
  "last_updated_at": "2025-12-18T10:35:00Z"
}
```

### WalletPassLog
```json
{
  "id": 1,
  "device_library_identifier": "device-id",
  "pass_type_identifier": "pass.com.example",
  "serial_number": "pass-001",
  "message": "Error al actualizar",
  "log_level": "error",
  "created_at": "2025-12-18T10:30:00Z"
}
```

---

## 🐛 Troubleshooting

### "Unauthorized"
```
✅ Verificar header Authorization: ApplePass {token}
✅ Verificar espacio entre ApplePass y token
```

### "Invalid push token"
```
✅ Token debe ser 64 caracteres hexadecimales
✅ Solo caracteres 0-9 y a-f
```

### "Pass not found"
```
✅ Verificar pass_type_identifier
✅ Verificar serial_number
✅ ¿Se creó el pass primero?
```

### "Certificate not found"
```
✅ Ruta en .env es relativa a storage/app/private/
✅ Ejecutar: php artisan config:clear && php artisan cache:clear
```

---

## 📞 Soporte

Para más información:
- Consultar [API_WALLET_PASSES.md](./API_WALLET_PASSES.md) para detalles técnicos
- Consultar [QUICK_REFERENCE.md](./QUICK_REFERENCE.md) para referencias rápidas
- Consultar [INTEGRATION_GUIDE.md](./INTEGRATION_GUIDE.md) para ejemplos de código

---

## 📝 Notas de versión

**v1.0** (18 de diciembre de 2025)
- ✅ Implementación completa de 7 endpoints
- ✅ Suite de tests con 13 casos
- ✅ Documentación completa en 3 documentos
- ✅ Soporte para iOS, Android, Web
- ✅ Ejemplos de integración para 6 lenguajes

---

## 🔗 Especificaciones

- [Apple Wallet Web Service Reference](https://developer.apple.com/library/archive/documentation/PassKit/Conceptual/PassKit_ProgrammingGuide/WebService.html)
- [PassKit Documentation](https://developer.apple.com/documentation/passkit)
- [Apple Developer](https://developer.apple.com/)

---

**Última actualización:** 18 de diciembre de 2025  
**Estado:** ✅ Producción lista
