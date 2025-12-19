# FAQ y Mejores Prácticas - API de Wallet Passes

Respuestas a preguntas frecuentes y mejores prácticas para utilizar el API.

## 📋 Tabla de contenidos

1. [Preguntas frecuentes](#preguntas-frecuentes)
2. [Mejores prácticas](#mejores-prácticas)
3. [Patrones comunes](#patrones-comunes)
4. [Optimización](#optimización)
5. [Seguridad](#seguridad)

---

## Preguntas frecuentes

### P: ¿Qué es un "push token"?

**R:** Es un identificador hexadecimal único que Apple genera para cada combinación de dispositivo-pass. Se usa para enviar notificaciones push cuando un pass se actualiza. Debe tener exactamente 64 caracteres hexadecimales (0-9, a-f).

```
Válido: aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa (64 chars)
Inválido: aaaa (muy corto)
Inválido: gggg... (contiene caracteres no hexadecimales)
```

---

### P: ¿Cómo autenticar las solicitudes?

**R:** Incluir el header `Authorization` con el formato exacto:

```
Authorization: ApplePass {authToken}
```

Donde `{authToken}` es reemplazado por tu token de autenticación.

```bash
# ✅ Correcto
curl -H "Authorization: ApplePass my-secret-token"

# ❌ Incorrecto - falta espacio
curl -H "Authorization: ApplePassmy-secret-token"

# ❌ Incorrecto - formato incorrecto
curl -H "Authorization: Bearer my-secret-token"
```

---

### P: ¿Dónde se configura el `webServiceURL` para el registro de dispositivos?

**R:** Se configura automáticamente al crear el pass. El API establece:

```
webServiceURL = {APP_URL}/api/v1
```

Donde `APP_URL` viene de `config('app.url')` en Laravel.

**Opcionalmente puedes personalizar:**

```bash
# Crear pass con webServiceURL personalizada
curl -X POST http://localhost:8000/api/v1/passes \
  -H "Authorization: ApplePass my-token" \
  -H "Content-Type: application/json" \
  -d '{
    "pass_type_identifier": "pass.com.example",
    "serial_number": "pass-001",
    "template_type": "generic",
    "data": {
      "description": "Mi pass",
      "organizationName": "Mi Empresa",
      "webServiceURL": "https://api.miempresa.com/api/v1"
    }
  }'
```

**¿Por qué es importante?**
- Apple Wallet usa esta URL para registrar dispositivos
- Sin `webServiceURL`, no se pueden registrar dispositivos
- Se incrusta en el archivo `.pkpass`

---

### P: ¿El pass se crea automáticamente cuando se registra un dispositivo?

**R:** No. El flujo correcto es:

1. **Crear** el pass con `POST /api/v1/passes`
2. **Descargar** el archivo con `GET /api/v1/passes/{type}/{serial}`
3. El usuario lo agrega a Apple Wallet
4. **Registrar** el dispositivo con `POST /api/v1/devices/.../registrations/.../`

---

### P: ¿Qué sucede si actualizo un pass sin dispositivos registrados?

**R:** La actualización se guarda en la BD, pero no se envían notificaciones push. Cuando un dispositivo se registre posteriormente, verá el pass actualizado.

---

### P: ¿Puedo tener múltiples dispositivos registrados para el mismo pass?

**R:** Sí, absolutamente. Cada dispositivo tiene su propio registro con su push token única.

```
Pass A
├── Dispositivo 1 (iPhone) - Token: aaaa...
├── Dispositivo 2 (iPad) - Token: bbbb...
└── Dispositivo 3 (iPhone) - Token: cccc...
```

Cuando actualices el pass A, se notificará a los 3 dispositivos.

---

### P: ¿Cómo sé si un dispositivo recibió la notificación?

**R:** Hay dos formas:

1. **Endpoint de logs**: El dispositivo reporta con `POST /api/v1/log`
2. **Endpoint de cambios**: El dispositivo consulta con `GET /api/v1/devices/.../registrations/...?lastUpdated=...`

Si consulta cambios después de tu update, significa que recibió la notificación.

---

### P: ¿Qué es el `lastUpdated`?

**R:** Es un timestamp UNIX (segundos desde época) que marca la última vez que el cliente conoce sobre cambios. Se usa para consultar qué passes han cambiado desde esa fecha.

```bash
# Consultar cambios desde hace 1 hora
curl "...?lastUpdated=$(($(date +%s) - 3600))"

# Respuesta:
{
  "lastUpdated": 1702999600,
  "serialNumbers": ["pass-001", "pass-002"]
}
```

---

### P: ¿Puedo actualizar un pass que no existe?

**R:** No. Devuelve `404 Not Found`. Primero debes crear el pass con `POST /api/v1/passes`.

```
Flujo correcto:
1. POST /api/v1/passes (crear)
2. PUT /api/v1/passes/{type}/{serial} (actualizar)

Flujo incorrecto:
1. PUT /api/v1/passes/{type}/{serial} (actualizar sin crear)
   → 404 Not Found
```

---

### P: ¿Se puede crear un pass con el mismo `serial_number` y tipo?

**R:** No. Es una combinación única. Si intentas crear un duplicate:

- Debe usar diferente `serial_number`, o
- Debe cambiar para actualizar con `PUT` en lugar de `POST`

---

### P: ¿Cuánto tiempo almacenan los passes?

**R:** Indefinidamente, hasta que los elimines. No hay expiración automática.

---

### P: ¿Puedo usar el API desde el navegador?

**R:** Sí, pero debes configurar CORS si llamas desde JavaScript. Para desarrollo local, puedes deshabilitar CORS o usar un proxy.

```javascript
// ❌ Sin CORS configurado:
// Cross-Origin Request Blocked (en desarrollo)

// ✅ Solución 1: Configure CORS en Laravel
// app/Http/Middleware/Cors.php

// ✅ Solución 2: Use proxy en desarrollo
// Postman/Insomnia no tienen este problema
```

---

### P: ¿Hay límite de requests por segundo?

**R:** No está implementado rate limiting actualmente. En producción, se recomienda agregarlo.

---

### P: ¿Qué datos puedo incluir en `data` de un pass?

**R:** Cualquier JSON que desees. El formato es flexible. Ejemplos:

```json
{
  "description": "Required",
  "organizationName": "Required",
  "foregroundColor": "Optional",
  "backgroundColor": "Optional",
  "customField1": "Any value",
  "customField2": 12345,
  "customObject": {
    "nested": "value"
  }
}
```

Cuando actualices, se fusionan recursivamente los datos.

---

### P: ¿Cómo descargar todos los passes de una vez?

**R:** Debes iterar sobre ellos. No hay endpoint bulk:

```javascript
const passes = await fetchAllPasses(); // Tu lógica para obtener lista

for (const pass of passes) {
  const blob = await client.downloadPass(pass.type, pass.serial);
  // Procesar blob
}
```

---

### P: ¿Puedo descargar un pass sin autenticación?

**R:** No, todos los endpoints excepto `POST /api/v1/log` requieren `Authorization`.

---

### P: ¿Qué sucede si no incluyo el header `Authorization`?

**R:** Respuesta `401 Unauthorized`:

```json
{
  "error": "Unauthorized"
}
```

---

## Mejores prácticas

### 1. 🔒 Seguridad de tokens

**Nunca** compartas tu `authToken`:

```javascript
// ❌ MAL - Exponiendo el token en código público
const token = "my-secret-token-12345";

// ✅ BIEN - Variables de entorno
const token = process.env.WALLET_AUTH_TOKEN;

// ✅ BIEN - Desde servidor backend
const response = await fetch('/api/get-wallet-pass', {
  // Servidor hace la autenticación internamente
});
```

---

### 2. 📝 Logging

Implementa logging para debugging:

```php
// Laravel
Log::info('Pass created', [
    'pass_type' => $passType,
    'serial_number' => $serialNumber,
    'timestamp' => now()
]);

Log::error('Device registration failed', [
    'device_id' => $deviceId,
    'reason' => $e->getMessage()
]);
```

```javascript
// JavaScript
console.log('[Wallet] Pass created:', {
    passType,
    serialNumber,
    timestamp: new Date().toISOString()
});
```

---

### 3. ✅ Validación

Valida datos antes de enviar:

```javascript
function validatePushToken(token) {
    if (typeof token !== 'string') return false;
    if (token.length !== 64) return false;
    return /^[0-9a-f]{64}$/i.test(token);
}

function validatePassType(type) {
    return /^pass\.[a-z0-9]+\.[a-z0-9]+/.test(type);
}

// Uso
if (!validatePushToken(token)) {
    throw new Error('Invalid push token format');
}
```

---

### 4. 🔄 Manejo de errores

Implementa reintentos para fallos temporales:

```javascript
async function retryRequest(fn, maxRetries = 3) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            return await fn();
        } catch (error) {
            if (attempt === maxRetries) throw error;
            
            const delay = Math.pow(2, attempt) * 1000; // Exponential backoff
            await new Promise(r => setTimeout(r, delay));
        }
    }
}

// Uso
const pass = await retryRequest(() => 
    client.downloadPass(type, serial)
);
```

---

### 5. 📊 Caché

Cachea passes descargados si es posible:

```javascript
class CachedWalletPassClient {
    constructor(client) {
        this.client = client;
        this.cache = new Map();
    }
    
    async downloadPass(type, serial) {
        const key = `${type}:${serial}`;
        
        if (this.cache.has(key)) {
            return this.cache.get(key);
        }
        
        const blob = await this.client.downloadPass(type, serial);
        this.cache.set(key, blob);
        
        return blob;
    }
}
```

---

### 6. ⏱️ Timestamps correctos

Usa timestamps UNIX en segundos, no milisegundos:

```javascript
// ✅ Correcto
const lastUpdated = Math.floor(Date.now() / 1000); // Segundos
// lastUpdated = 1702999600

// ❌ Incorrecto
const lastUpdated = Date.now(); // Milisegundos
// lastUpdated = 1702999600000 (demasiado grande)
```

---

### 7. 🔐 HTTPS en producción

Siempre usa HTTPS:

```javascript
// ✅ Producción
const baseURL = "https://api.example.com";

// ✅ Desarrollo local
const baseURL = "http://localhost:8000";

// ❌ Nunca en producción
const baseURL = "http://api.example.com"; // Inseguro
```

---

### 8. 📱 Manejo de timeouts

Configura timeouts apropiados:

```javascript
const client = axios.create({
    timeout: 10000, // 10 segundos
    baseURL: baseURL,
    headers: {
        'Authorization': `ApplePass ${token}`
    }
});
```

---

### 9. 🔔 Notificaciones push

Aunque está simulado, prepara para integración real:

```php
// app/Services/WalletPushNotificationService.php

public function notifyPassUpdate(string $passId) {
    // Obtener dispositivos registrados
    $devices = WalletPassRegistration::where('wallet_pass_id', $passId)
        ->pluck('push_token');
    
    foreach ($devices as $token) {
        // TODO: Implementar integración con APNs
        // $this->apnsService->sendNotification($token, $data);
        
        Log::info('Push notification would be sent', ['token' => $token]);
    }
}
```

---

### 10. 🧹 Limpieza de datos

Implementa archivado de logs antiguos:

```php
// app/Console/Commands/ArchiveWalletLogs.php

// Eliminar logs más antiguos de 90 días
WalletPassLog::where('created_at', '<', now()->subDays(90))->delete();
```

---

## Patrones comunes

### Patrón: Crear y descargar pass

```javascript
const client = new WalletPassClient(baseURL, token);

// 1. Crear
const created = await client.createPass({
    pass_type_identifier: 'pass.com.ticket',
    serial_number: 'ticket-' + Date.now(),
    template_type: 'eventTicket',
    data: {
        description: 'Mi entrada',
        date: '2025-12-25T20:00:00Z'
    }
});

// 2. Descargar inmediatamente
const blob = await client.downloadPass(
    created.pass_type_identifier,
    created.serial_number
);

// 3. Descargar al navegador
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'ticket.pkpass';
a.click();
```

---

### Patrón: Monitorear cambios

```javascript
async function monitorChanges(deviceId, passType) {
    let lastUpdated = Math.floor(Date.now() / 1000);
    const checkInterval = 5 * 60 * 1000; // Cada 5 minutos
    
    setInterval(async () => {
        const response = await client.getUpdatedPasses(
            deviceId,
            passType,
            lastUpdated
        );
        
        if (response.serialNumbers.length > 0) {
            console.log('📱 New updates available:', response.serialNumbers);
            
            // Descargar passes actualizados
            for (const serial of response.serialNumbers) {
                const blob = await client.downloadPass(passType, serial);
                // Procesar...
            }
            
            lastUpdated = response.lastUpdated;
        }
    }, checkInterval);
}
```

---

### Patrón: Batch operations

```javascript
async function createMultiplePasses(passConfigs) {
    const results = [];
    
    for (const config of passConfigs) {
        try {
            const pass = await client.createPass(config);
            results.push({ success: true, pass });
        } catch (error) {
            results.push({ success: false, error: error.message });
        }
    }
    
    return results;
}

// Uso
const configs = [
    { serial_number: 'pass-001', ... },
    { serial_number: 'pass-002', ... },
    { serial_number: 'pass-003', ... }
];

const results = await createMultiplePasses(configs);
console.log(`Created ${results.filter(r => r.success).length} passes`);
```

---

## Optimización

### Índices de base de datos

Ya está optimizado con índices en:
- `wallet_passes(pass_type_identifier, serial_number)` - Búsqueda rápida
- `wallet_passes(updated_at)` - Consultas de cambios
- `wallet_pass_registrations(device_library_identifier, pass_type_identifier, serial_number)` - Registros únicos

---

### Consultas eficientes

Evitar N+1 queries:

```php
// ❌ Ineficiente - N+1 queries
foreach ($passes as $pass) {
    $registrations = $pass->registrations;
}

// ✅ Eficiente - Una sola query
$passes = WalletPass::with('registrations')->get();
foreach ($passes as $pass) {
    $registrations = $pass->registrations;
}
```

---

### Caché de configuración

```php
// Cachear valores que no cambian frecuentemente
$passTypeId = Cache::remember('wallet_pass_type_id', 3600, function () {
    return config('wallet.pass_type_identifier');
});
```

---

## Seguridad

### 1. Validación de input

```php
// En WalletPassController
$validated = $request->validate([
    'pushToken' => 'required|regex:/^[0-9a-f]{64}$/i',
    'pass_type_identifier' => 'required|string',
    'serial_number' => 'required|string',
]);
```

---

### 2. Rate limiting

```php
// app/Http/Middleware/ThrottleWalletRequests.php
Route::middleware('throttle:60,1')->group(function () {
    // 60 requests por minuto
});
```

---

### 3. Validación de certificados

Los certificados están protegidos:
```
storage/app/private/passgenerator/certs/
```

No son accesibles públicamente.

---

### 4. Headers de seguridad

```php
// config/wallet.php
'security_headers' => [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block'
]
```

---

### 5. Encriptación de datos sensibles

Para tokens push:

```php
$pushToken = encrypt($plainToken);
// Almacenar en BD: ENCRYPT($pushToken)
```

---

## Resumen de mejores prácticas

✅ Siempre incluye el header `Authorization`  
✅ Valida datos antes de enviar  
✅ Usa HTTPS en producción  
✅ Implementa reintentos con backoff exponencial  
✅ Cachea datos que no cambian frecuentemente  
✅ Loguea todas las operaciones importantes  
✅ Maneja errores de forma apropiada  
✅ Nunca expongas tokens en código público  
✅ Usa timestamps UNIX en segundos  
✅ Monitorea cambios periódicamente  

---

**¿Tienes una pregunta que no está en este documento?**  
Consulta:
- [API_WALLET_PASSES.md](./API_WALLET_PASSES.md) - Documentación técnica
- [QUICK_REFERENCE.md](./QUICK_REFERENCE.md) - Referencia rápida
- [INTEGRATION_GUIDE.md](./INTEGRATION_GUIDE.md) - Ejemplos de código
