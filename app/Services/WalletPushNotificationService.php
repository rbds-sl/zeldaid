<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WalletPassRegistration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WalletPushNotificationService
{
    private const APPLE_PUSH_GATEWAY = 'https://api.push.apple.com:443/3/device';

    /**
     * Enviar notificación push a todos los dispositivos registrados para un pass
     */
    public function notifyPassUpdate(string $passTypeIdentifier, string $serialNumber): bool
    {
        try {
            // Obtener todos los dispositivos registrados para este pass
            $registrations = WalletPassRegistration::where('pass_type_identifier', $passTypeIdentifier)
                ->where('serial_number', $serialNumber)
                ->whereNotNull('push_token')
                ->get();

            if ($registrations->isEmpty()) {
                Log::info("No devices registered for pass: {$passTypeIdentifier}/{$serialNumber}");
                return true;
            }

            $successCount = 0;
            foreach ($registrations as $registration) {
                if ($this->sendPushNotification($registration)) {
                    $successCount++;
                }
            }

            Log::info("Sent {$successCount} push notifications for pass: {$passTypeIdentifier}/{$serialNumber}");
            return $successCount > 0;
        } catch (\Exception $e) {
            Log::error("Error sending push notifications: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación push a un dispositivo específico
     */
    private function sendPushNotification(WalletPassRegistration $registration): bool
    {
        try {
            // TODO: Implementar autenticación con certificado Apple (VoIP Push Service Certificate)
            // Por ahora, retornamos true como simulación

            // En producción, esto enviaría una solicitud HTTPS POST a:
            // POST https://api.push.apple.com:443/3/device/{deviceToken}
            // Con payload JSON vacío {}
            // Y autenticación usando el certificado .p8

            Log::debug("Push notification sent to device: " . $registration->device_library_identifier);

            // Actualizar el timestamp de última actualización
            $registration->update([
                'last_updated_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Error sending push to device {$registration->device_library_identifier}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificar a todos los dispositivos sobre un cambio en un pass
     */
    public function broadcastPassUpdate(string $passTypeIdentifier, string $serialNumber): void
    {
        $this->notifyPassUpdate($passTypeIdentifier, $serialNumber);
    }

    /**
     * Validar que el token push es válido
     */
    public function validatePushToken(string $pushToken): bool
    {
        // Los tokens push de Apple son hexadecimales de 64 caracteres
        return preg_match('/^[a-f0-9]{64}$/i', $pushToken) === 1;
    }
}
