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
        'liquidada',//NEW
        'tipo_cafe', //NEW
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
        static::created(function ($entrega) {
            // Buscar el registro de inventario acumulado para esta combinación
            $inventario = Inventario::where('humedad', $entrega->humedad)
                ->where('tipo_cafe', $entrega->tipo_cafe)
                ->where('humedad', $entrega->humedad)
                ->first();

            if ($inventario) {
                // Incrementar la cantidad y el peso neto acumulado
                $inventario->increment('cantidad_sacos', $entrega->cantidad_sacos);
                $inventario->increment('peso_neto', $entrega->peso_neto);
                // Actualizar la fecha de última modificación
                $inventario->fecha = $entrega->fecha_entrega;
                $inventario->save();
            } else {
                // Crear un nuevo registro acumulado si no existe
                Inventario::create([
                    // No incluimos 'entrega_id' porque es acumulado
                    'fecha' => $entrega->fecha_entrega,
                    'tipo' => 'ENTRADA',
                    'tipo_cafe' => $entrega->tipo_cafe,
                    'humedad' => $entrega->humedad,
                    'cantidad_sacos' => $entrega->cantidad_sacos,
                    'peso_neto' => $entrega->peso_neto,
                ]);
            }
        });

        // Similarmente, en el evento 'updated' se debería calcular la diferencia
        // entre el nuevo valor y el anterior y actualizar el registro acumulado.
        static::updated(function ($entrega) {
            // Se requiere tener el valor original para calcular la diferencia
            $originalCantidad = $entrega->getOriginal('cantidad_sacos');
            $originalPesoNeto = $entrega->getOriginal('peso_neto');

            // Buscar el registro acumulado correspondiente. Puede requerirse identificar
            // el registro anterior en caso de que se haya modificado el tipo de café o humedad.
            $inventario = Inventario::where('tipo', 'ENTRADA')
                ->where('tipo_cafe', $entrega->tipo_cafe)
                ->where('humedad', $entrega->humedad)
                ->first();

            if ($inventario) {
                // Calcular las diferencias
                $diffCantidad = $entrega->cantidad_sacos - $originalCantidad;
                $diffPesoNeto = $entrega->peso_neto - $originalPesoNeto;

                // Actualizar acumulados
                $inventario->increment('cantidad', $diffCantidad);
                $inventario->increment('peso_neto', $diffPesoNeto);
                // Actualizar fecha si es necesario
                $inventario->fecha = $entrega->fecha_entrega;
                $inventario->save();
            } else {
                \Log::warning("No se encontró registro acumulado para actualizar la Entrega ID: {$entrega->id}");
            }
        });

        // En 'deleted', se resta la cantidad y el peso neto del registro acumulado
        static::deleted(function ($entrega) {
            $inventario = Inventario::where('tipo', 'ENTRADA')
                ->where('tipo_cafe', $entrega->tipo_cafe)
                ->where('humedad', $entrega->humedad)
                ->first();

            if ($inventario) {
                $inventario->decrement('cantidad', $entrega->cantidad_sacos);
                $inventario->decrement('peso_neto', $entrega->peso_neto);
                $inventario->save();
            } else {
                \Log::warning("No se encontró registro acumulado para eliminar la Entrega ID: {$entrega->id}");
            }
        });
    }

}
