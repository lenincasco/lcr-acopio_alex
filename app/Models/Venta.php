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
        'peso_neto',
        'humedad',
        'imperfeccion',
        'tipo_cafe',
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
        static::created(function ($venta) {

            $inventarioGeneral = Inventario::where('humedad', $venta->humedad)
                ->where('tipo_cafe', $venta->tipo_cafe)
                ->where('humedad', $venta->humedad)
                ->first();

            if ($inventarioGeneral) {
                // Reducir la cantidad y el peso neto del inventario general
                $inventarioGeneral->cantidad_sacos -= $venta->cantidad_sacos;
                $inventarioGeneral->peso_neto -= $venta->peso_neto;

                $inventarioGeneral->cantidad_sacos = max(0, $inventarioGeneral->cantidad_sacos);
                $inventarioGeneral->peso_neto = max(0, $inventarioGeneral->peso_neto);

                $inventarioGeneral->save();
                \Log::info("Inventario actualizado después de la venta ID: {$venta->id}");
            } else {
                \Log::warning("No se encontró registro de inventario general para actualizar después de la venta ID: {$venta->id}");
            }
        });
    }
}
