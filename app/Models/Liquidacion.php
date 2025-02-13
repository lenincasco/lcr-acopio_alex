<?php

namespace App\Models;

use App\Services\AplicarCreditoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Liquidacion extends Model
{
    protected $table = 'liquidaciones';

    protected $fillable = [
        'fecha_liquidacion',
        'usuario_liquida',
        'observacion',
        'tipo_cambio',
        'total_qq_liquidados',
        'precio_liquidacion',
        'estado',
        'proveedor_id', //NEW
        'monto_bruto', // RENAMED from monto_total
        'monto_credito_aplicado', // NEW
        'monto_neto', // NEW Monto neto final después de aplicar crédito
    ];
    protected static function booted()
    {
        static::saved(function (Liquidacion $liquidacion) {
            DB::afterCommit(function () use ($liquidacion) {
                $liquidacion->refresh();

                $detalles = $liquidacion->detalles()->get();
                \Log::info('Cantidad de detalles: ' . $detalles->count());

                foreach ($detalles as $detalle) {
                    $entrega = \App\Models\Entrega::find($detalle->entrega_id);
                    if ($entrega && !$entrega->liquidada) {
                        $entrega->liquidada = true;
                        $entrega->save();
                    }
                }

                if ($liquidacion->monto_credito_aplicado > 0) {
                    AplicarCreditoService::aplicarCreditoEnPrestamos(
                        $liquidacion->proveedor_id,
                        $liquidacion->monto_credito_aplicado
                    );
                }
            });
        });
    }

    // NEW relation with User
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_liquida');
    }

    // Relación con el modelo Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Relación con los detalles de la liquidación
    public function detalles()
    {
        return $this->hasMany(DetalleLiquidacion::class);
    }
}
