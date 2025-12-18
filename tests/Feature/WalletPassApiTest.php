<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\WalletPass;
use App\Models\WalletPassLog;
use App\Models\WalletPassRegistration;
use Tests\TestCase;

class WalletPassApiTest extends TestCase
{
    private string $passTypeIdentifier = 'pass.com.example.test';
    private string $serialNumber = 'test-pass-123';
    private string $deviceLibraryIdentifier = 'device-library-id-12345';
    private string $authToken = 'valid-auth-token-12345';
    private string $authHeader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authHeader = 'ApplePass ' . $this->authToken;
    }

    private function getValidPushToken(): string
    {
        return 'a' . str_repeat('b', 63); // 64 caracteres hexadecimales
    }

    /**
     * Test: Registrar un dispositivo para notificaciones
     */
    public function test_register_device_successfully(): void
    {
        $response = $this->postJson(
            "/api/v1/devices/{$this->deviceLibraryIdentifier}/registrations/{$this->passTypeIdentifier}/{$this->serialNumber}",
            ['pushToken' => $this->getValidPushToken()],
            ['Authorization' => $this->authHeader]
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('wallet_pass_registrations', [
            'device_library_identifier' => $this->deviceLibraryIdentifier,
            'pass_type_identifier' => $this->passTypeIdentifier,
            'serial_number' => $this->serialNumber,
        ]);
    }

    /**
     * Test: Registrar dispositivo sin autorización
     */
    public function test_register_device_without_auth(): void
    {
        $response = $this->postJson(
            "/api/v1/devices/{$this->deviceLibraryIdentifier}/registrations/{$this->passTypeIdentifier}/{$this->serialNumber}",
            ['pushToken' => $this->getValidPushToken()]
        );

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    /**
     * Test: Registrar dispositivo con push token inválido
     */
    public function test_register_device_with_invalid_push_token(): void
    {
        $response = $this->postJson(
            "/api/v1/devices/{$this->deviceLibraryIdentifier}/registrations/{$this->passTypeIdentifier}/{$this->serialNumber}",
            ['pushToken' => 'invalid-token'],
            ['Authorization' => $this->authHeader]
        );

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid push token']);
    }

    /**
     * Test: Desregistrar dispositivo
     */
    public function test_unregister_device_successfully(): void
    {
        // Primero crear un registro
        WalletPassRegistration::create([
            'device_library_identifier' => $this->deviceLibraryIdentifier,
            'pass_type_identifier' => $this->passTypeIdentifier,
            'serial_number' => $this->serialNumber,
            'push_token' => $this->getValidPushToken(),
            'registered_at' => now(),
        ]);

        $response = $this->deleteJson(
            "/api/v1/devices/{$this->deviceLibraryIdentifier}/registrations/{$this->passTypeIdentifier}/{$this->serialNumber}",
            [],
            ['Authorization' => $this->authHeader]
        );

        $response->assertStatus(200);
        $this->assertDatabaseMissing('wallet_pass_registrations', [
            'device_library_identifier' => $this->deviceLibraryIdentifier,
        ]);
    }

    /**
     * Test: Obtener passes actualizados
     */
    public function test_get_updated_passes(): void
    {
        $oldTimestamp = now()->subHours(2)->timestamp;

        // Crear registros
        WalletPassRegistration::create([
            'device_library_identifier' => $this->deviceLibraryIdentifier,
            'pass_type_identifier' => $this->passTypeIdentifier,
            'serial_number' => $this->serialNumber,
            'push_token' => $this->getValidPushToken(),
            'last_updated_at' => now(),
            'registered_at' => now(),
        ]);

        $response = $this->getJson(
            "/api/v1/devices/{$this->deviceLibraryIdentifier}/registrations/{$this->passTypeIdentifier}?lastUpdated={$oldTimestamp}",
            ['Authorization' => $this->authHeader]
        );

        $response->assertStatus(200)
            ->assertJsonStructure(['lastUpdated', 'serialNumbers'])
            ->assertJsonCount(1, 'serialNumbers');
    }

    /**
     * Test: Crear un nuevo pass
     */
    public function test_create_pass_successfully(): void
    {
        $passData = [
            'pass_type_identifier' => $this->passTypeIdentifier,
            'serial_number' => $this->serialNumber,
            'template_type' => 'generic',
            'data' => [
                'description' => 'Test Pass',
                'organizationName' => 'Test Organization',
                'foregroundColor' => 'rgb(255, 0, 0)',
            ],
        ];

        $response = $this->postJson(
            '/api/v1/passes',
            $passData,
            ['Authorization' => $this->authHeader]
        );

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'pass_type_identifier', 'serial_number']);

        $this->assertDatabaseHas('wallet_passes', [
            'pass_type_identifier' => $this->passTypeIdentifier,
            'serial_number' => $this->serialNumber,
            'template_type' => 'generic',
        ]);
    }

    /**
     * Test: Actualizar un pass existente
     */
    public function test_update_pass_successfully(): void
    {
        // Crear un pass primero
        WalletPass::create([
            'pass_type_identifier' => $this->passTypeIdentifier,
            'serial_number' => $this->serialNumber,
            'data' => ['description' => 'Original'],
            'template_type' => 'generic',
        ]);

        $updateData = [
            'data' => [
                'description' => 'Updated Description',
                'newField' => 'new value',
            ],
        ];

        $response = $this->putJson(
            "/api/v1/passes/{$this->passTypeIdentifier}/{$this->serialNumber}",
            $updateData,
            ['Authorization' => $this->authHeader]
        );

        $response->assertStatus(200);

        $pass = WalletPass::findByTypeAndSerial($this->passTypeIdentifier, $this->serialNumber);
        $this->assertEquals('Updated Description', $pass->data['description']);
        $this->assertEquals('new value', $pass->data['newField']);
    }

    /**
     * Test: Actualizar pass que no existe
     */
    public function test_update_nonexistent_pass(): void
    {
        $response = $this->putJson(
            "/api/v1/passes/nonexistent/nonexistent",
            ['data' => ['field' => 'value']],
            ['Authorization' => $this->authHeader]
        );

        $response->assertStatus(404)
            ->assertJson(['error' => 'Pass not found']);
    }

    /**
     * Test: Obtener pass individual (.pkpass)
     */
    public function test_get_pass_file(): void
    {
        // Crear un pass
        WalletPass::create([
            'pass_type_identifier' => $this->passTypeIdentifier,
            'serial_number' => $this->serialNumber,
            'data' => [
                'description' => 'Test Pass',
                'organizationName' => 'Test Org',
                'foregroundColor' => 'rgb(0, 0, 0)',
                'backgroundColor' => 'rgb(255, 255, 255)',
            ],
            'template_type' => 'generic',
        ]);

        $response = $this->get(
            "/api/v1/passes/{$this->passTypeIdentifier}/{$this->serialNumber}",
            ['Authorization' => $this->authHeader]
        );

        // El archivo binario se devuelve correctamente
        $this->assertNotEmpty($response->getContent());
    }

    /**
     * Test: Obtener pass que no existe
     */
    public function test_get_nonexistent_pass(): void
    {
        $response = $this->get(
            "/api/v1/passes/nonexistent/nonexistent",
            ['Authorization' => $this->authHeader]
        );

        $response->assertStatus(404);
    }

    /**
     * Test: Registrar logs de error
     */
    public function test_log_error_from_device(): void
    {
        $logData = [
            'deviceLibraryIdentifier' => $this->deviceLibraryIdentifier,
            'logs' => [
                [
                    'message' => 'Device error 1',
                    'passTypeIdentifier' => $this->passTypeIdentifier,
                    'serialNumber' => $this->serialNumber,
                ],
                [
                    'message' => 'Device error 2',
                    'passTypeIdentifier' => $this->passTypeIdentifier,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/log', $logData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('wallet_pass_logs', [
            'device_library_identifier' => $this->deviceLibraryIdentifier,
            'message' => 'Device error 1',
            'log_level' => 'error',
        ]);

        $this->assertDatabaseHas('wallet_pass_logs', [
            'device_library_identifier' => $this->deviceLibraryIdentifier,
            'message' => 'Device error 2',
            'log_level' => 'error',
        ]);
    }

    /**
     * Test: Verificar que el registro se actualiza al registrar nuevamente
     */
    public function test_register_device_updates_existing_registration(): void
    {
        $oldToken = 'a' . str_repeat('1', 63);
        
        // Crear registro inicial
        WalletPassRegistration::create([
            'device_library_identifier' => $this->deviceLibraryIdentifier,
            'pass_type_identifier' => $this->passTypeIdentifier,
            'serial_number' => $this->serialNumber,
            'push_token' => $oldToken,
            'registered_at' => now(),
        ]);

        // Registrar nuevamente con nuevo token
        $response = $this->postJson(
            "/api/v1/devices/{$this->deviceLibraryIdentifier}/registrations/{$this->passTypeIdentifier}/{$this->serialNumber}",
            ['pushToken' => $this->getValidPushToken()],
            ['Authorization' => $this->authHeader]
        );

        $response->assertStatus(201);

        // Verificar que el token fue actualizado
        $registration = WalletPassRegistration::findRegistration(
            $this->deviceLibraryIdentifier,
            $this->passTypeIdentifier,
            $this->serialNumber
        );

        $this->assertEquals($this->getValidPushToken(), $registration->push_token);
    }

    /**
     * Test: Flujo completo - crear pass, registrar dispositivo, actualizar pass
     */
    public function test_complete_workflow(): void
    {
        // 1. Crear un pass
        $createResponse = $this->postJson(
            '/api/v1/passes',
            [
                'pass_type_identifier' => $this->passTypeIdentifier,
                'serial_number' => $this->serialNumber,
                'template_type' => 'generic',
                'data' => ['description' => 'My Pass'],
            ],
            ['Authorization' => $this->authHeader]
        );
        $createResponse->assertStatus(201);

        // 2. Registrar dispositivo para notificaciones
        $registerResponse = $this->postJson(
            "/api/v1/devices/{$this->deviceLibraryIdentifier}/registrations/{$this->passTypeIdentifier}/{$this->serialNumber}",
            ['pushToken' => $this->getValidPushToken()],
            ['Authorization' => $this->authHeader]
        );
        $registerResponse->assertStatus(201);

        // 3. Actualizar el pass
        $updateResponse = $this->putJson(
            "/api/v1/passes/{$this->passTypeIdentifier}/{$this->serialNumber}",
            ['data' => ['description' => 'Updated Pass']],
            ['Authorization' => $this->authHeader]
        );
        $updateResponse->assertStatus(200);

        // 4. Verificar que el dispositivo puede obtener las actualizaciones
        $updateTime = now()->subMinutes(1)->timestamp;
        $getUpdatesResponse = $this->getJson(
            "/api/v1/devices/{$this->deviceLibraryIdentifier}/registrations/{$this->passTypeIdentifier}?lastUpdated={$updateTime}",
            ['Authorization' => $this->authHeader]
        );
        $getUpdatesResponse->assertStatus(200)
            ->assertJsonPath('serialNumbers.0', $this->serialNumber);

        // 5. Descargar el pass actualizado
        $getPassResponse = $this->get(
            "/api/v1/passes/{$this->passTypeIdentifier}/{$this->serialNumber}",
            ['Authorization' => $this->authHeader]
        );
        $this->assertNotEmpty($getPassResponse->getContent());
    }
}
