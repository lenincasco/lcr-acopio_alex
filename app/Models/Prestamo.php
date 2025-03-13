<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{

    protected $table = 'prestamos';


    protected $fillable = [
        'proveedor_id',
        'fecha_desembolso',
        'interes',
        'plazo_meses',
        'monto',
        'monto_interes',
        'monto_total',
        'saldo',
        'mora',
        'volumen_estimado',
        'precio_referencia',
        'fecha_vencimiento',
        'fecha_ultimo_pago',
        'tipo_cambio',
        'estado',
        'razon_anula',
        'fecha_anula',
        'usuario_anula',
    ];

    // Definir la relación con el proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class); // Un préstamo pertenece a un proveedor
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class);
    }

    protected static function booted()
    {
        // Cuando se crea una venta, se reduce el inventario.
        static::creating(function ($prestamo) {
            $prestamo->saldo = $prestamo->monto;
            $prestamo->fecha_ultimo_pago = $prestamo->fecha_desembolso;
        });
    }
}