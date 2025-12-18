# Guía de Integración - API de Wallet Passes

Instrucciones para integrar el API de Wallet Passes en aplicaciones cliente.

## Tabla de contenidos

1. [iOS/Swift](#iosswift)
2. [Android](#android)
3. [Web/JavaScript](#webjavascript)
4. [PHP/Laravel](#phplaravel)
5. [Python](#python)
6. [Postman](#postman)

---

## iOS/Swift

### 1. Importar PassKit

```swift
import PassKit

class WalletPassIntegration {
    let baseURL = "https://api.example.com"
    let authToken = "your-auth-token"
    
    // Descargar y agregar pass a Wallet
    func addPassToWallet(passTypeIdentifier: String, serialNumber: String) {
        let urlString = "\(baseURL)/api/v1/passes/\(passTypeIdentifier)/\(serialNumber)"
        
        var request = URLRequest(url: URL(string: urlString)!)
        request.setValue("ApplePass \(authToken)", forHTTPHeaderField: "Authorization")
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            guard let data = data, error == nil else {
                print("Error: \(error?.localizedDescription ?? "Unknown")")
                return
            }
            
            // Guardar archivo .pkpass temporalmente
            let tempURL = FileManager.default.temporaryDirectory.appendingPathComponent("pass.pkpass")
            try? data.write(to: tempURL)
            
            // Presentar PKAddPassesViewController
            let pass = try? PKPass(data: data)
            let controller = PKAddPassesViewController(pass: pass!)
            self.present(controller, animated: true)
        }.resume()
    }
    
    // Registrar dispositivo para notificaciones
    func registerDevice(deviceId: String, pushToken: String, passType: String, serial: String) {
        let urlString = "\(baseURL)/api/v1/devices/\(deviceId)/registrations/\(passType)/\(serial)"
        
        var request = URLRequest(url: URL(string: urlString)!)
        request.httpMethod = "POST"
        request.setValue("ApplePass \(authToken)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body: [String: Any] = ["pushToken": pushToken]
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            guard let httpResponse = response as? HTTPURLResponse, httpResponse.statusCode == 201 else {
                print("Registration failed: \(error?.localizedDescription ?? "Unknown")")
                return
            }
            print("✅ Device registered successfully")
        }.resume()
    }
}
```

### 2. Detectar cambios de pass

```swift
import PassKit

class PassKitObserver: NSObject, PKPassLibraryChangeObserver {
    let integration = WalletPassIntegration()
    
    func passLibraryDidChange(notification: NSNotification) {
        // Cuando se agrega un pass, registrar dispositivo
        print("📱 Pass library changed")
        
        // Obtener push token y registrar
        // (Depende de tu implementación de push notifications)
    }
}
```

---

## Android

### 1. Descargar archivo .pkpass

```kotlin
import android.content.Context
import java.io.File
import okhttp3.OkHttpClient
import okhttp3.Request

class WalletPassIntegration(private val context: Context) {
    private val client = OkHttpClient()
    private val baseURL = "https://api.example.com"
    private val authToken = "your-auth-token"
    
    fun downloadPass(passTypeIdentifier: String, serialNumber: String): File? {
        val url = "$baseURL/api/v1/passes/$passTypeIdentifier/$serialNumber"
        
        val request = Request.Builder()
            .url(url)
            .addHeader("Authorization", "ApplePass $authToken")
            .build()
        
        return try {
            val response = client.newCall(request).execute()
            if (response.isSuccessful) {
                val body = response.body
                val file = File(context.cacheDir, "pass.pkpass")
                file.outputStream().use { outputStream ->
                    body?.byteStream()?.use { inputStream ->
                        inputStream.copyTo(outputStream)
                    }
                }
                file
            } else {
                null
            }
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }
    
    fun registerDevice(
        deviceId: String,
        pushToken: String,
        passType: String,
        serial: String
    ) {
        val url = "$baseURL/api/v1/devices/$deviceId/registrations/$passType/$serial"
        
        val jsonBody = """
            {
              "pushToken": "$pushToken"
            }
        """.trimIndent()
        
        val request = Request.Builder()
            .url(url)
            .post(okhttp3.RequestBody.create(null, jsonBody))
            .addHeader("Authorization", "ApplePass $authToken")
            .addHeader("Content-Type", "application/json")
            .build()
        
        client.newCall(request).enqueue(object : okhttp3.Callback {
            override fun onFailure(call: okhttp3.Call, e: java.io.IOException) {
                println("❌ Registration failed: ${e.message}")
            }
            
            override fun onResponse(call: okhttp3.Call, response: okhttp3.Response) {
                if (response.code == 201) {
                    println("✅ Device registered successfully")
                } else {
                    println("❌ Registration failed: ${response.code}")
                }
            }
        })
    }
}
```

### 2. Usar con Retrofit

```kotlin
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.*

interface WalletPassAPI {
    @GET("/api/v1/passes/{passType}/{serial}")
    suspend fun getPass(
        @Path("passType") passType: String,
        @Path("serial") serial: String,
        @Header("Authorization") auth: String
    ): okhttp3.ResponseBody
    
    @POST("/api/v1/devices/{deviceId}/registrations/{passType}/{serial}")
    suspend fun registerDevice(
        @Path("deviceId") deviceId: String,
        @Path("passType") passType: String,
        @Path("serial") serial: String,
        @Body request: RegisterDeviceRequest,
        @Header("Authorization") auth: String
    )
}

data class RegisterDeviceRequest(val pushToken: String)

// Uso
val retrofit = Retrofit.Builder()
    .baseUrl("https://api.example.com")
    .addConverterFactory(GsonConverterFactory.create())
    .build()

val api = retrofit.create(WalletPassAPI::class.java)
val authToken = "your-auth-token"
api.registerDevice("device-id", "pass.type", "serial", 
    RegisterDeviceRequest("token"), "ApplePass $authToken")
```

---

## Web/JavaScript

### 1. Usando Fetch API

```javascript
class WalletPassClient {
    constructor(baseURL, authToken) {
        this.baseURL = baseURL;
        this.authToken = authToken;
    }
    
    // Crear nuevo pass
    async createPass(passData) {
        const response = await fetch(`${this.baseURL}/api/v1/passes`, {
            method: 'POST',
            headers: {
                'Authorization': `ApplePass ${this.authToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(passData)
        });
        
        if (!response.ok) {
            throw new Error(`${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    }
    
    // Descargar pass
    async downloadPass(passTypeIdentifier, serialNumber) {
        const response = await fetch(
            `${this.baseURL}/api/v1/passes/${passTypeIdentifier}/${serialNumber}`,
            {
                headers: {
                    'Authorization': `ApplePass ${this.authToken}`
                }
            }
        );
        
        if (!response.ok) {
            throw new Error('Failed to download pass');
        }
        
        const blob = await response.blob();
        return blob;
    }
    
    // Registrar dispositivo
    async registerDevice(deviceId, pushToken, passType, serial) {
        const response = await fetch(
            `${this.baseURL}/api/v1/devices/${deviceId}/registrations/${passType}/${serial}`,
            {
                method: 'POST',
                headers: {
                    'Authorization': `ApplePass ${this.authToken}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ pushToken })
            }
        );
        
        return response.status === 201;
    }
    
    // Actualizar pass
    async updatePass(passTypeIdentifier, serialNumber, data) {
        const response = await fetch(
            `${this.baseURL}/api/v1/passes/${passTypeIdentifier}/${serialNumber}`,
            {
                method: 'PUT',
                headers: {
                    'Authorization': `ApplePass ${this.authToken}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ data })
            }
        );
        
        return response.json();
    }
    
    // Obtener cambios
    async getUpdatedPasses(deviceId, passType, lastUpdated) {
        const response = await fetch(
            `${this.baseURL}/api/v1/devices/${deviceId}/registrations/${passType}?lastUpdated=${lastUpdated}`,
            {
                headers: {
                    'Authorization': `ApplePass ${this.authToken}`
                }
            }
        );
        
        return response.json();
    }
}

// Uso
const client = new WalletPassClient('https://api.example.com', 'my-token');

// Crear pass
const newPass = await client.createPass({
    pass_type_identifier: 'pass.com.example',
    serial_number: 'pass-001',
    template_type: 'generic',
    data: {
        description: 'Mi primer pass',
        organizationName: 'Mi Empresa'
    }
});

// Descargar
const blob = await client.downloadPass('pass.com.example', 'pass-001');

// Registrar dispositivo
await client.registerDevice('device-id', 'aaaa...', 'pass.com.example', 'pass-001');
```

### 2. Usando Axios

```javascript
import axios from 'axios';

class WalletPassClient {
    constructor(baseURL, authToken) {
        this.client = axios.create({
            baseURL,
            headers: {
                'Authorization': `ApplePass ${authToken}`,
                'Content-Type': 'application/json'
            },
            responseType: 'blob'
        });
    }
    
    async downloadPass(passTypeIdentifier, serialNumber) {
        try {
            const response = await this.client.get(
                `/api/v1/passes/${passTypeIdentifier}/${serialNumber}`,
                { responseType: 'blob' }
            );
            return response.data;
        } catch (error) {
            console.error('Error downloading pass:', error);
            throw error;
        }
    }
    
    async registerDevice(deviceId, pushToken, passType, serial) {
        try {
            await this.client.post(
                `/api/v1/devices/${deviceId}/registrations/${passType}/${serial}`,
                { pushToken }
            );
            console.log('✅ Device registered');
        } catch (error) {
            console.error('❌ Registration failed:', error);
            throw error;
        }
    }
}
```

### 3. Botón de descarga

```html
<button id="addToWalletBtn">Add to Wallet</button>

<script>
const client = new WalletPassClient('https://api.example.com', 'my-token');

document.getElementById('addToWalletBtn').addEventListener('click', async () => {
    try {
        const blob = await client.downloadPass('pass.com.example', 'pass-001');
        
        // Crear descarga
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'pass.pkpass';
        a.click();
        
        // Registrar dispositivo
        const deviceId = 'user-' + Date.now();
        const pushToken = 'a'.repeat(64);
        await client.registerDevice(deviceId, pushToken, 'pass.com.example', 'pass-001');
        
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>
```

---

## PHP/Laravel

### 1. Client HTTP

```php
use GuzzleHttp\Client;

class WalletPassClient {
    private $client;
    private $baseURL;
    private $authToken;
    
    public function __construct($baseURL, $authToken) {
        $this->baseURL = $baseURL;
        $this->authToken = $authToken;
        $this->client = new Client();
    }
    
    public function createPass(array $passData) {
        $response = $this->client->post(
            $this->baseURL . '/api/v1/passes',
            [
                'headers' => [
                    'Authorization' => 'ApplePass ' . $this->authToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $passData
            ]
        );
        
        return json_decode($response->getBody(), true);
    }
    
    public function downloadPass($passTypeIdentifier, $serialNumber) {
        $response = $this->client->get(
            $this->baseURL . "/api/v1/passes/{$passTypeIdentifier}/{$serialNumber}",
            [
                'headers' => [
                    'Authorization' => 'ApplePass ' . $this->authToken
                ]
            ]
        );
        
        return $response->getBody()->getContents();
    }
    
    public function registerDevice($deviceId, $pushToken, $passType, $serial) {
        $response = $this->client->post(
            $this->baseURL . "/api/v1/devices/{$deviceId}/registrations/{$passType}/{$serial}",
            [
                'headers' => [
                    'Authorization' => 'ApplePass ' . $this->authToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => ['pushToken' => $pushToken]
            ]
        );
        
        return $response->getStatusCode() === 201;
    }
    
    public function updatePass($passTypeIdentifier, $serialNumber, array $data) {
        $response = $this->client->put(
            $this->baseURL . "/api/v1/passes/{$passTypeIdentifier}/{$serialNumber}",
            [
                'headers' => [
                    'Authorization' => 'ApplePass ' . $this->authToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => ['data' => $data]
            ]
        );
        
        return json_decode($response->getBody(), true);
    }
}

// Uso
$client = new WalletPassClient('https://api.example.com', 'my-token');

$pass = $client->createPass([
    'pass_type_identifier' => 'pass.com.example',
    'serial_number' => 'pass-001',
    'template_type' => 'generic',
    'data' => [
        'description' => 'Mi pass',
        'organizationName' => 'Mi Empresa'
    ]
]);

$content = $client->downloadPass('pass.com.example', 'pass-001');
file_put_contents('pass.pkpass', $content);
```

### 2. Laravel Service Provider

```php
// app/Services/WalletPassService.php
namespace App\Services;

use GuzzleHttp\Client;

class WalletPassService {
    private $client;
    
    public function __construct() {
        $this->client = new Client([
            'base_uri' => config('wallet.api_url'),
            'headers' => [
                'Authorization' => 'ApplePass ' . config('wallet.auth_token'),
                'Content-Type' => 'application/json'
            ]
        ]);
    }
    
    public function createPass(array $data) {
        $response = $this->client->post('/api/v1/passes', ['json' => $data]);
        return json_decode($response->getBody());
    }
    
    public function downloadPass($type, $serial) {
        return $this->client->get("/api/v1/passes/{$type}/{$serial}")->getBody();
    }
}

// config/wallet.php
return [
    'api_url' => env('WALLET_API_URL', 'http://localhost:8000'),
    'auth_token' => env('WALLET_AUTH_TOKEN'),
];

// Uso en controller
public function download() {
    $passContent = app(WalletPassService::class)->downloadPass(
        'pass.com.example',
        'pass-001'
    );
    
    return response($passContent)
        ->header('Content-Type', 'application/vnd.apple.pkpass')
        ->header('Content-Disposition', 'attachment; filename="pass.pkpass"');
}
```

---

## Python

### 1. Usando requests

```python
import requests
import json

class WalletPassClient:
    def __init__(self, base_url, auth_token):
        self.base_url = base_url
        self.auth_token = auth_token
        self.headers = {
            'Authorization': f'ApplePass {auth_token}',
            'Content-Type': 'application/json'
        }
    
    def create_pass(self, pass_data):
        response = requests.post(
            f'{self.base_url}/api/v1/passes',
            headers=self.headers,
            json=pass_data
        )
        response.raise_for_status()
        return response.json()
    
    def download_pass(self, pass_type, serial):
        response = requests.get(
            f'{self.base_url}/api/v1/passes/{pass_type}/{serial}',
            headers=self.headers
        )
        response.raise_for_status()
        return response.content
    
    def register_device(self, device_id, push_token, pass_type, serial):
        response = requests.post(
            f'{self.base_url}/api/v1/devices/{device_id}/registrations/{pass_type}/{serial}',
            headers=self.headers,
            json={'pushToken': push_token}
        )
        return response.status_code == 201
    
    def update_pass(self, pass_type, serial, data):
        response = requests.put(
            f'{self.base_url}/api/v1/passes/{pass_type}/{serial}',
            headers=self.headers,
            json={'data': data}
        )
        response.raise_for_status()
        return response.json()

# Uso
client = WalletPassClient('https://api.example.com', 'my-token')

# Crear pass
pass_data = {
    'pass_type_identifier': 'pass.com.example',
    'serial_number': 'pass-001',
    'template_type': 'generic',
    'data': {
        'description': 'Mi pass',
        'organizationName': 'Mi Empresa'
    }
}
result = client.create_pass(pass_data)
print(f"✅ Pass created: {result['id']}")

# Descargar
content = client.download_pass('pass.com.example', 'pass-001')
with open('pass.pkpass', 'wb') as f:
    f.write(content)

# Registrar dispositivo
success = client.register_device('device-id', 'a'*64, 'pass.com.example', 'pass-001')
print(f"{'✅' if success else '❌'} Device registration")
```

### 2. Usando aiohttp (async)

```python
import aiohttp
import asyncio

class AsyncWalletPassClient:
    def __init__(self, base_url, auth_token):
        self.base_url = base_url
        self.auth_token = auth_token
        self.headers = {
            'Authorization': f'ApplePass {auth_token}',
            'Content-Type': 'application/json'
        }
    
    async def create_pass(self, session, pass_data):
        async with session.post(
            f'{self.base_url}/api/v1/passes',
            headers=self.headers,
            json=pass_data
        ) as resp:
            return await resp.json()
    
    async def download_pass(self, session, pass_type, serial):
        async with session.get(
            f'{self.base_url}/api/v1/passes/{pass_type}/{serial}',
            headers=self.headers
        ) as resp:
            return await resp.read()

# Uso
async def main():
    client = AsyncWalletPassClient('https://api.example.com', 'my-token')
    
    async with aiohttp.ClientSession() as session:
        pass_data = {...}
        result = await client.create_pass(session, pass_data)
        print(result)

asyncio.run(main())
```

---

## Postman

### 1. Importar colección

Crear una colección con los siguientes requests:

**Variables globales:**
```
base_url: http://localhost:8000
auth_token: my-token-123
```

### 2. POST - Crear pass

```
POST {{base_url}}/api/v1/passes

Headers:
Authorization: ApplePass {{auth_token}}
Content-Type: application/json

Body:
{
  "pass_type_identifier": "pass.com.example",
  "serial_number": "pass-001",
  "template_type": "generic",
  "data": {
    "description": "Mi pass",
    "organizationName": "Mi Empresa"
  }
}
```

### 3. GET - Descargar pass

```
GET {{base_url}}/api/v1/passes/pass.com.example/pass-001

Headers:
Authorization: ApplePass {{auth_token}}

Send and download binary file
```

### 4. POST - Registrar dispositivo

```
POST {{base_url}}/api/v1/devices/device-id/registrations/pass.com.example/pass-001

Headers:
Authorization: ApplePass {{auth_token}}
Content-Type: application/json

Body:
{
  "pushToken": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
}
```

### 5. PUT - Actualizar pass

```
PUT {{base_url}}/api/v1/passes/pass.com.example/pass-001

Headers:
Authorization: ApplePass {{auth_token}}
Content-Type: application/json

Body:
{
  "data": {
    "description": "Pass actualizado",
    "organizationName": "Nueva Empresa"
  }
}
```

### 6. GET - Obtener cambios

```
GET {{base_url}}/api/v1/devices/device-id/registrations/pass.com.example?lastUpdated=1702929600

Headers:
Authorization: ApplePass {{auth_token}}
```

---

## Checklist de integración

- [ ] Obtener `auth_token` del servidor
- [ ] Configurar `base_url` del API
- [ ] Crear un pass de prueba
- [ ] Descargar el archivo .pkpass
- [ ] Registrar dispositivo con token válido (64 hex chars)
- [ ] Actualizar pass y verificar cambios
- [ ] Consultar passes actualizados
- [ ] Implementar manejo de errores
- [ ] Agregar logging
- [ ] Validar en producción

---

**Para más información, ver:** [API_WALLET_PASSES.md](./API_WALLET_PASSES.md)
