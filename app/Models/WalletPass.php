<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletPass extends Model
{
    protected $table = 'wallet_passes';

    protected $fillable = [
        'pass_type_identifier',
        'serial_number',
        'data',
        'template_type',
        'version_updated_at',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'version_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con registros de dispositivos
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(WalletPassRegistration::class, 'pass_type_identifier', 'pass_type_identifier');
    }

    /**
     * Scope para filtrar por tipo de pass
     */
    public function scopeByPassType($query, string $passTypeIdentifier)
    {
        return $query->where('pass_type_identifier', $passTypeIdentifier);
    }

    /**
     * Scope para filtrar por número de serie
     */
    public function scopeBySerialNumber($query, string $serialNumber)
    {
        return $query->where('serial_number', $serialNumber);
    }

    /**
     * Obtener un pass por tipo y número de serie
     */
    public static function findByTypeAndSerial(string $passTypeIdentifier, string $serialNumber): ?self
    {
        return self::where('pass_type_identifier', $passTypeIdentifier)
            ->where('serial_number', $serialNumber)
            ->first();
    }

    /**
     * Marcar como actualizado
     */
    public function markAsUpdated(): void
    {
        $this->update([
            'version_updated_at' => now(),
        ]);
    }
}
