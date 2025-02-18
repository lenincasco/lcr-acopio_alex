<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Salida extends Model
{
    /** @use HasFactory<\Database\Factories\VentaFactory> */
    use HasFactory;

    protected $table = 'salidas';

    protected $fillable = [
        'cliente_id',
        'fecha_salida',
        'peso_bruto',
        'tara_saco',
        'cantidad_sacos',
        'peso_saco',
        'peso_neto',
        'humedad',
        'calidad',
        'tipo_cafe',
        'creado_por',
        'editado_por',

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

        static::created(function ($salida) {

            $inventarioGeneral = Inventario::where('tipo', 'entrada')
                ->where('tipo_cafe', $salida->tipo_cafe)
                ->where('humedad', $salida->humedad)
                ->first();

            if ($inventarioGeneral) {
                // Reducir la cantidad y el peso neto del inventario general
                $inventarioGeneral->cantidad_sacos -= $salida->cantidad_sacos;
                $inventarioGeneral->peso_neto -= $salida->peso_neto;

                $inventarioGeneral->cantidad_sacos = max(0, $inventarioGeneral->cantidad_sacos);
                $inventarioGeneral->peso_neto = max(0, $inventarioGeneral->peso_neto);

                $inventarioGeneral->save();
                \Log::info("Inventario actualizado después de la venta ID: {$salida->id}");
            } else {
                \Log::warning("No se encontró registro de inventario general para actualizar después de la salida ID: {$salida->id}");
            }
        });
    }
}
