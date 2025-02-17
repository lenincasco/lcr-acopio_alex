<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
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
        // Cuando se crea una venta, se reduce el inventario.
        static::created(function ($venta) {
            static::updateInventoryOnCreate($venta);
        });

        // En una actualización, se ajusta el inventario según la diferencia entre
        // los valores nuevos y los originales.
        static::updated(function ($venta) {
            static::updateInventoryOnUpdate($venta);
        });

        // Al eliminar una venta, se reincorpora la cantidad vendida al inventario.
        static::deleted(function ($venta) {
            static::updateInventoryOnDelete($venta);
        });
    }

    private static function updateInventoryOnCreate(Venta $venta): void
    {
        $inventario = Inventario::where('humedad', $venta->humedad)
            ->where('tipo_cafe', $venta->tipo_cafe)
            ->first();

        if ($inventario) {
            // Disminuye la cantidad y el peso neto disponibles en el inventario.
            $inventario->decrement('cantidad_sacos', $venta->cantidad_sacos);
            $inventario->decrement('peso_neto', $venta->peso_neto);
        } else {
            \Log::warning("No se encontró registro de inventario para la venta ID: {$venta->id}");
        }
    }

    private static function updateInventoryOnDelete(Venta $venta): void
    {
        $inventario = Inventario::where('humedad', $venta->humedad)
            ->where('tipo_cafe', $venta->tipo_cafe)
            ->first();

        if ($inventario) {
            // Reincorpora los sacos y el peso al inventario.
            $inventario->increment('cantidad_sacos', $venta->cantidad_sacos);
            $inventario->increment('peso_neto', $venta->peso_neto);
        } else {
            \Log::warning("No se encontró registro de inventario para la venta ID: {$venta->id}");
        }
    }

    private static function updateInventoryOnUpdate(Venta $venta): void
    {
        // Obtener los valores anteriores
        $oldCantidad = $venta->getOriginal('cantidad_sacos');
        $oldPesoNeto = $venta->getOriginal('peso_neto');

        // Calcular la diferencia: (valor original - valor nuevo)
        $deltaCantidad = $oldCantidad - $venta->cantidad_sacos;
        $deltaPesoNeto = $oldPesoNeto - $venta->peso_neto;

        $inventario = Inventario::where('humedad', $venta->humedad)
            ->where('tipo_cafe', $venta->tipo_cafe)
            ->first();

        if ($inventario) {
            // Si la diferencia es positiva, significa que la venta se redujo
            // y se debe reincorporar esa diferencia al inventario.
            if ($deltaCantidad > 0) {
                $inventario->increment('cantidad_sacos', $deltaCantidad);
            } elseif ($deltaCantidad < 0) {
                // Si es negativa, significa que la venta aumentó,
                // se debe decrementar el inventario en la diferencia.
                $inventario->decrement('cantidad_sacos', abs($deltaCantidad));
            }

            if ($deltaPesoNeto > 0) {
                $inventario->increment('peso_neto', $deltaPesoNeto);
            } elseif ($deltaPesoNeto < 0) {
                $inventario->decrement('peso_neto', abs($deltaPesoNeto));
            }
        } else {
            \Log::warning("No se encontró registro de inventario para la venta ID: {$venta->id}");
        }
    }
}
