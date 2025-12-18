<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WalletPass;
use App\Models\WalletPassLog;
use App\Models\WalletPassRegistration;
use App\Services\WalletPassGeneratorService;
use App\Services\WalletPushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WalletPassController extends Controller
{
    public function __construct(
        private readonly WalletPassGeneratorService $passGeneratorService,
        private readonly WalletPushNotificationService $pushNotificationService,
    ) {}

    /**
     * Registrar dispositivo para recibir notificaciones de actualización de pass
     * POST /v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}
     */
    public function registerDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber
    ): JsonResponse {
        try {
            // Validar que la solicitud tenga el authorization header correcto
            $authHeader = $request->header('Authorization');
            if (!$this->validateAuthorizationToken($authHeader)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $pushToken = $request->input('pushToken');

            // Validar que el token push sea válido
            if (!$this->pushNotificationService->validatePushToken($pushToken)) {
                return response()->json(['error' => 'Invalid push token'], 400);
            }

            // Crear o actualizar el registro del dispositivo
            WalletPassRegistration::updateOrCreate(
                [
                    'device_library_identifier' => $deviceLibraryIdentifier,
                    'pass_type_identifier' => $passTypeIdentifier,
                    'serial_number' => $serialNumber,
                ],
                [
                    'push_token' => $pushToken,
                    'registered_at' => now(),
                    'last_updated_at' => now(),
                ]
            );

            WalletPassLog::logInfo(
                $deviceLibraryIdentifier,
                'Dispositivo registrado para notificaciones',
                $passTypeIdentifier,
                $serialNumber
            );

            return response()->json([], 201);
        } catch (\Exception $e) {
            WalletPassLog::logError(
                $deviceLibraryIdentifier ?? 'unknown',
                'Error al registrar dispositivo: ' . $e->getMessage()
            );
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Desregistrar dispositivo
     * DELETE /v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}
     */
    public function unregisterDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber
    ): JsonResponse {
        try {
            $authHeader = $request->header('Authorization');
            if (!$this->validateAuthorizationToken($authHeader)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Eliminar el registro del dispositivo
            WalletPassRegistration::where('device_library_identifier', $deviceLibraryIdentifier)
                ->where('pass_type_identifier', $passTypeIdentifier)
                ->where('serial_number', $serialNumber)
                ->delete();

            WalletPassLog::logInfo(
                $deviceLibraryIdentifier,
                'Dispositivo desregistrado',
                $passTypeIdentifier,
                $serialNumber
            );

            return response()->json([], 200);
        } catch (\Exception $e) {
            WalletPassLog::logError(
                $deviceLibraryIdentifier ?? 'unknown',
                'Error al desregistrar dispositivo: ' . $e->getMessage()
            );
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Obtener passes actualizados para un dispositivo
     * GET /v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}?lastUpdated={timestamp}
     */
    public function getUpdatedPasses(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier
    ): JsonResponse {
        try {
            $authHeader = $request->header('Authorization');
            if (!$this->validateAuthorizationToken($authHeader)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $lastUpdated = (int)$request->input('lastUpdated', 0);

            // Obtener todos los passes del dispositivo que fueron actualizados después de lastUpdated
            $passes = WalletPassRegistration::getUpdatedAfter(
                $deviceLibraryIdentifier,
                $passTypeIdentifier,
                $lastUpdated
            );

            return response()->json([
                'lastUpdated' => (int)now()->timestamp,
                'serialNumbers' => $passes,
            ], 200);
        } catch (\Exception $e) {
            WalletPassLog::logError(
                $deviceLibraryIdentifier ?? 'unknown',
                'Error al obtener passes actualizados: ' . $e->getMessage()
            );
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Obtener pass individual (archivo .pkpass)
     * GET /v1/passes/{passTypeIdentifier}/{serialNumber}
     */
    public function getPass(
        Request $request,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        try {
            $authHeader = $request->header('Authorization');
            if (!$this->validateAuthorizationToken($authHeader)) {
                return response('Unauthorized', 401);
            }

            // Generar el archivo .pkpass
            $pkpass = $this->passGeneratorService->generatePassFile($passTypeIdentifier, $serialNumber);

            if (!$pkpass) {
                return response('Pass not found', 404);
            }

            return response($pkpass, 200, [
                'Content-Type' => 'application/vnd.apple.pkpass',
                'Content-Disposition' => 'attachment; filename="pass.pkpass"',
                'Content-Length' => strlen($pkpass),
            ]);
        } catch (\Exception $e) {
            WalletPassLog::logError(
                'unknown',
                'Error al obtener pass: ' . $e->getMessage(),
                $passTypeIdentifier,
                $serialNumber
            );
            return response('Server error', 500);
        }
    }

    /**
     * Log de errores desde el dispositivo
     * POST /v1/log
     */
    public function logError(Request $request): JsonResponse
    {
        try {
            $logs = $request->input('logs', []);
            $deviceLibraryIdentifier = $request->input('deviceLibraryIdentifier', 'unknown');

            foreach ($logs as $log) {
                WalletPassLog::logError(
                    $deviceLibraryIdentifier,
                    $log['message'] ?? 'Unknown error',
                    $log['passTypeIdentifier'] ?? null,
                    $log['serialNumber'] ?? null,
                    $log['context'] ?? null
                );
            }

            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Crear un nuevo pass
     * POST /v1/passes
     */
    public function createPass(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pass_type_identifier' => 'required|string',
                'serial_number' => 'required|string',
                'data' => 'required|array',
                'template_type' => 'string|in:boarding_pass,coupon,generic,event_ticket,store_card,loyalty_card',
            ]);

            $pass = $this->passGeneratorService->savePass($validated);

            // Notificar a los dispositivos sobre el nuevo pass
            $this->pushNotificationService->broadcastPassUpdate(
                $validated['pass_type_identifier'],
                $validated['serial_number']
            );

            return response()->json([
                'id' => $pass->id,
                'pass_type_identifier' => $pass->pass_type_identifier,
                'serial_number' => $pass->serial_number,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar un pass existente
     * PUT /v1/passes/{passTypeIdentifier}/{serialNumber}
     */
    public function updatePass(
        Request $request,
        string $passTypeIdentifier,
        string $serialNumber
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'data' => 'required|array',
            ]);

            $pass = $this->passGeneratorService->updatePass($passTypeIdentifier, $serialNumber, $validated);

            if (!$pass) {
                return response()->json(['error' => 'Pass not found'], 404);
            }

            // Notificar a los dispositivos sobre la actualización
            $this->pushNotificationService->broadcastPassUpdate($passTypeIdentifier, $serialNumber);

            return response()->json([
                'id' => $pass->id,
                'pass_type_identifier' => $pass->pass_type_identifier,
                'serial_number' => $pass->serial_number,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Validar token de autorización
     */
    private function validateAuthorizationToken(?string $authHeader): bool
    {
        if (!$authHeader) {
            return false;
        }

        // El formato debe ser: "ApplePass {token}"
        $parts = explode(' ', $authHeader);
        if (count($parts) !== 2 || $parts[0] !== 'ApplePass') {
            return false;
        }

        $token = $parts[1];

        // TODO: Validar el token contra tu base de datos o sistema de autenticación
        // Por ahora, validamos que no esté vacío
        return !empty($token);
    }
}
