<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPassRegistration extends Model
{
    protected $table = 'wallet_pass_registrations';

    protected $fillable = [
        'device_library_identifier',
        'pass_type_identifier',
        'serial_number',
        'push_token',
        'registered_at',
        'last_updated_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el pass
     */
    public function pass(): BelongsTo
    {
        return $this->belongsTo(WalletPass::class, 'pass_type_identifier', 'pass_type_identifier');
    }

    /**
     * Scope para filtrar por dispositivo
     */
    public function scopeByDevice($query, string $deviceLibraryIdentifier)
    {
        return $query->where('device_library_identifier', $deviceLibraryIdentifier);
    }

    /**
     * Scope para filtrar por tipo de pass
     */
    public function scopeByPassType($query, string $passTypeIdentifier)
    {
        return $query->where('pass_type_identifier', $passTypeIdentifier);
    }

    /**
     * Obtener un registro específico
     */
    public static function findRegistration(
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber
    ): ?self {
        return self::where('device_library_identifier', $deviceLibraryIdentifier)
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->where('serial_number', $serialNumber)
            ->first();
    }

    /**
     * Obtener todos los passes de un dispositivo para un tipo
     */
    public static function getDevicePasses(
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier
    ) {
        return self::where('device_library_identifier', $deviceLibraryIdentifier)
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->get();
    }

    /**
     * Obtener passes actualizados después de una fecha
     */
    public static function getUpdatedAfter(
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        int $timestamp
    ) {
        return self::where('device_library_identifier', $deviceLibraryIdentifier)
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->where('last_updated_at', '>', \Carbon\Carbon::createFromTimestamp($timestamp))
            ->pluck('serial_number')
            ->toArray();
    }

    /**
     * Marcar como actualizado
     */
    public function markAsUpdated(): void
    {
        $this->update([
            'last_updated_at' => now(),
        ]);
    }
}
