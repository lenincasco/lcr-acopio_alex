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
        'volumen_estimado',
        'precio_referencia',
        'fecha_vencimiento',
        'fecha_ultimo_pago',
        'tipo_cambio',
    ];

    // Definir la relación con el proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class); // Un préstamo pertenece a un proveedor
    }
}