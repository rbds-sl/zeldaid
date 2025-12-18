<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WalletPass;
use Illuminate\Support\Facades\Storage;

class WalletPassGeneratorService
{
    /**
     * Generar archivo .pkpass para un pass almacenado
     */
    public function generatePassFile(string $passTypeIdentifier, string $serialNumber): ?string
    {
        try {
            // Obtener el pass de la base de datos
            $walletPass = WalletPass::findByTypeAndSerial($passTypeIdentifier, $serialNumber);

            if (!$walletPass) {
                return null;
            }

            // Obtener datos del pass
            $passData = $walletPass->data ?? [];

            // Crear instancia del generador de passes
            $pass = new \Byte5\PassGenerator($serialNumber, true);

            // Configurar definición del pass
            $definition = [
                'description' => $passData['description'] ?? 'Pass',
                'formatVersion' => 1,
                'organizationName' => $passData['organizationName'] ?? config('passgenerator.organization_name', 'Organization'),
                'passTypeIdentifier' => $passTypeIdentifier,
                'serialNumber' => $serialNumber,
                'teamIdentifier' => $passData['teamIdentifier'] ?? config('passgenerator.team_identifier', 'teamid'),
            ];

            // Agregar campos opcionales
            if (!empty($passData['foregroundColor'])) {
                $definition['foregroundColor'] = $passData['foregroundColor'];
            }
            if (!empty($passData['backgroundColor'])) {
                $definition['backgroundColor'] = $passData['backgroundColor'];
            }
            if (!empty($passData['barcode'])) {
                $definition['barcode'] = $passData['barcode'];
            }

            // Agregar tipo de pass específico (boardingPass, coupon, generic, etc.)
            if (!empty($passData['boardingPass'])) {
                $definition['boardingPass'] = $passData['boardingPass'];
            }
            if (!empty($passData['coupon'])) {
                $definition['coupon'] = $passData['coupon'];
            }
            if (!empty($passData['generic'])) {
                $definition['generic'] = $passData['generic'];
            }

            $pass->setPassDefinition($definition);

            // Agregar assets si están configurados
            if (!empty($passData['assets'])) {
                foreach ($passData['assets'] as $assetPath => $assetName) {
                    if (file_exists($assetPath)) {
                        $pass->addAsset($assetPath, $assetName);
                    }
                }
            }

            // Generar el archivo .pkpass
            $pkpass = $pass->create();

            return $pkpass;
        } catch (\Exception $e) {
            \Log::error("Error generating pass file: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Guardar un pass en el almacenamiento
     */
    public function savePass(array $passData): WalletPass
    {
        $passTypeIdentifier = $passData['pass_type_identifier'];
        $serialNumber = $passData['serial_number'];

        return WalletPass::updateOrCreate(
            [
                'pass_type_identifier' => $passTypeIdentifier,
                'serial_number' => $serialNumber,
            ],
            [
                'data' => $passData['data'] ?? [],
                'template_type' => $passData['template_type'] ?? 'generic',
                'created_by' => $passData['created_by'] ?? null,
                'version_updated_at' => now(),
            ]
        );
    }

    /**
     * Actualizar un pass existente
     */
    public function updatePass(string $passTypeIdentifier, string $serialNumber, array $newData): ?WalletPass
    {
        $pass = WalletPass::findByTypeAndSerial($passTypeIdentifier, $serialNumber);

        if (!$pass) {
            return null;
        }

        // Fusionar datos existentes con los nuevos
        $mergedData = array_merge($pass->data ?? [], $newData['data'] ?? []);

        $pass->update([
            'data' => $mergedData,
            'version_updated_at' => now(),
        ]);

        return $pass;
    }

    /**
     * Obtener los datos de un pass
     */
    public function getPassData(string $passTypeIdentifier, string $serialNumber): ?array
    {
        $pass = WalletPass::findByTypeAndSerial($passTypeIdentifier, $serialNumber);

        return $pass ? $pass->data : null;
    }
}
