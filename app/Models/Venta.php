<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    /** @use HasFactory<\Database\Factories\VentaFactory> */
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id',
        'fecha_venta',
        'peso_bruto',
        'tara_saco',
        'cantidad_sacos',
        'peso_saco',
        'peso_neto',
        'humedad',
        'imperfeccion',
        'creado_por',
        'editado_por',

        'precio_unitario',
        'iva',
        'monto_bruto',
        'monto_neto',
        'observaciones',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function editadoPor()
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    protected static function booted()
    {
        parent::booted();

        static::created(function ($venta) {

            $inventarioGeneral = Inventario::where('tipo', 'entrada')->latest()->first();

            if ($inventarioGeneral) {
                // Reducir la cantidad y el peso neto del inventario general
                $inventarioGeneral->cantidad -= $venta->cantidad_sacos;
                $inventarioGeneral->peso_neto -= $venta->peso_neto;

                $inventarioGeneral->cantidad = max(0, $inventarioGeneral->cantidad);
                $inventarioGeneral->peso_neto = max(0, $inventarioGeneral->peso_neto);

                $inventarioGeneral->save();
                \Log::info("Inventario actualizado después de la venta ID: {$venta->id}");
            } else {
                \Log::warning("No se encontró registro de inventario general para actualizar después de la venta ID: {$venta->id}");
            }
        });
    }
}
