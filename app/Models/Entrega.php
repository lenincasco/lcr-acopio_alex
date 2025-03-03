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
        'calidad',
        'creado_por',
        'editado_por',
        'tipo_entrega', //Puede ser de tipo "COMPRA" o "ENTREGA"
        'liquidada',//NEW
        'tipo_cafe', //NEW
        'precio_compra',//NEW
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
        // Al crear una entrega, se incrementa el inventario.
        static::created(function ($entrega) {
            $inventario = Inventario::where('humedad', $entrega->humedad)
                ->where('tipo_cafe', $entrega->tipo_cafe)
                ->first();

            if ($inventario) {
                // Se incrementa el inventario con los valores de la entrega.
                $inventario->increment('cantidad_sacos', $entrega->cantidad_sacos);
                $inventario->increment('peso_neto', $entrega->peso_neto);
                $inventario->fecha = $entrega->fecha_entrega;
                // Los métodos increment/decrement ejecutan la query inmediatamente, 
                // pero como modificamos 'fecha' hacemos un save() para persistirlo.
                $inventario->save();
            } else {
                // Si no existe, se crea un nuevo registro acumulado.
                Inventario::create([
                    'fecha' => $entrega->fecha_entrega,
                    'tipo' => 'ENTRADA',
                    'tipo_cafe' => $entrega->tipo_cafe,
                    'humedad' => $entrega->humedad,
                    'cantidad_sacos' => $entrega->cantidad_sacos,
                    'peso_neto' => $entrega->peso_neto,
                ]);
            }
        });

        // Al actualizar una entrega, se debe "revertir" el efecto original y luego aplicar el nuevo.
        static::updated(function ($entrega) {
            $originalCantidad = $entrega->getOriginal('cantidad_sacos');
            $originalPesoNeto = $entrega->getOriginal('peso_neto');

            $inventario = Inventario::where('humedad', $entrega->humedad)
                ->where('tipo_cafe', $entrega->tipo_cafe)
                ->first();
            if ($inventario) {
                // Revertir el efecto original: se decrementa el inventario con los valores antiguos.
                $inventario->decrement('cantidad_sacos', $originalCantidad);
                $inventario->decrement('peso_neto', $originalPesoNeto);

                // Aplicar el nuevo efecto: se incrementa el inventario con los nuevos valores.
                $inventario->increment('cantidad_sacos', $entrega->cantidad_sacos);
                $inventario->increment('peso_neto', $entrega->peso_neto);

                $inventario->fecha = $entrega->fecha_entrega;
                $inventario->save();
            } else {
                \Log::warning("No se encontró registro acumulado para actualizar la Entrega ID: {$entrega->id}");
            }
        });

        // Al eliminar una entrega, se "revierte" su efecto, es decir, se decrementa el inventario.
        static::deleted(function ($entrega) {
            $inventario = Inventario::where('humedad', $entrega->humedad)
                ->where('tipo_cafe', $entrega->tipo_cafe)
                ->first();

            if ($inventario) {
                $inventario->decrement('cantidad_sacos', $entrega->cantidad_sacos);
                $inventario->decrement('peso_neto', $entrega->peso_neto);
                $inventario->save();
            } else {
                \Log::warning("No se encontró registro acumulado para eliminar la Entrega ID: {$entrega->id}");
            }
        });
    }


}
