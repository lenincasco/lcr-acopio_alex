<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{

    protected $table = 'entregas';

    protected $fillable = [
        'proveedor_id',
        'fecha_entrega',
        'peso_bruto',
        'tara_saco',
        'cantidad_sacos',
        'peso_saco',
        'peso_neto',
        'quintalaje_liquidable',
        'humedad',
        'imperfeccion',
        'creado_por',
        'editado_por',
        'tipo_entrega', //Puede ser de tipo "COMPRA" o "ENTREGA"
        'cliente_general',
        'liquidada'//NEW
    ];
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function editadoPor()
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    public function detallesLiquidacion() // Nombre de la relación usado en LiquidacionResource
    {
        return $this->hasMany(DetalleLiquidacion::class, 'entrega_id'); // Relación hasMany con DetalleLiquidacion usando 'entrega_id' como clave foránea
    }

    //NEW update Inventario
    protected static function booted()
    {
        static::creating(function ($entrega) {
            if (is_null($entrega->proveedor_id)) {
                // Generar un ID alternativo basado en fecha y hora (timestamp en milisegundos)
                $fallbackProveedorId = 'cliente_general_' . now()->getPreciseTimestamp(3);

                // Asignar el ID alternativo al proveedor_id
                $entrega->proveedor_id = $fallbackProveedorId;

                \Log::warning("Proveedor ID nulo detectado al crear Entrega. Generando ID alternativo: {$fallbackProveedorId}. Considera revisar la lógica de creación de Entregas.");
            }
        });

        static::created(function ($entrega) {
            Inventario::create([
                'entrega_id' => $entrega->id,
                'fecha' => $entrega->fecha_entrega,
                'tipo' => 'entrada',
                'cantidad' => $entrega->cantidad_sacos,
                'peso_neto' => $entrega->peso_neto,
            ]);
        });

        static::updated(function ($entrega) {
            $inventario = Inventario::where('entrega_id', $entrega->id)->first();

            if ($inventario) {
                $inventario->fecha = $entrega->fecha_entrega;
                $inventario->tipo = $entrega->tipo_entrega;
                $inventario->cantidad = $entrega->cantidad_sacos;
                $inventario->peso_neto = $entrega->peso_neto;

                $inventario->save();
            } else {
                \Log::warning("No se encontró Inventario para la Entrega ID: {$entrega->id} al actualizar Entrega.");
            }
        });

        static::deleted(function ($entrega) {
            $inventario = Inventario::where('entrega_id', $entrega->id)->first();

            if ($inventario) {
                $inventario->delete();
                \Log::info("Inventario eliminado para Entrega ID: {$entrega->id} debido a la eliminación de la Entrega.");
            } else {
                \Log::warning("No se encontró Inventario para la Entrega ID: {$entrega->id} al eliminar Entrega. Posible inconsistencia.");
            }
        });
    }

}
