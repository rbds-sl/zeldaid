<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletPassLog extends Model
{
    protected $table = 'wallet_pass_logs';

    protected $fillable = [
        'device_library_identifier',
        'message',
        'log_level',
        'pass_type_identifier',
        'serial_number',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope para filtrar por nivel de log
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('log_level', $level);
    }

    /**
     * Scope para filtrar por dispositivo
     */
    public function scopeByDevice($query, string $deviceLibraryIdentifier)
    {
        return $query->where('device_library_identifier', $deviceLibraryIdentifier);
    }

    /**
     * Scope para filtrar errores
     */
    public function scopeErrors($query)
    {
        return $query->where('log_level', 'error');
    }

    /**
     * Scope para filtrar por pass
     */
    public function scopeByPass($query, string $passTypeIdentifier, string $serialNumber)
    {
        return $query->where('pass_type_identifier', $passTypeIdentifier)
            ->where('serial_number', $serialNumber);
    }

    /**
     * Registrar un nuevo log
     */
    public static function logError(
        string $deviceLibraryIdentifier,
        string $message,
        ?string $passTypeIdentifier = null,
        ?string $serialNumber = null,
        ?array $context = null
    ): self {
        return self::create([
            'device_library_identifier' => $deviceLibraryIdentifier,
            'message' => $message,
            'log_level' => 'error',
            'pass_type_identifier' => $passTypeIdentifier,
            'serial_number' => $serialNumber,
            'context' => $context,
        ]);
    }

    /**
     * Registrar un log informativo
     */
    public static function logInfo(
        string $deviceLibraryIdentifier,
        string $message,
        ?string $passTypeIdentifier = null,
        ?string $serialNumber = null,
        ?array $context = null
    ): self {
        return self::create([
            'device_library_identifier' => $deviceLibraryIdentifier,
            'message' => $message,
            'log_level' => 'info',
            'pass_type_identifier' => $passTypeIdentifier,
            'serial_number' => $serialNumber,
            'context' => $context,
        ]);
    }
}
