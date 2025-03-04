<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleLiquidacion extends Model
{
    protected $table = 'liquidacion_detalle'; // Nombre correcto de la tabla

    protected $fillable = [
        'liquidacion_id',
        'entrega_id',
        'monto_entrega',
        'qq_liquidado',
    ];

    public function liquidacion()
    {
        return $this->belongsTo(Liquidacion::class);
    }

    public function entrega()
    {
        return $this->belongsTo(Entrega::class);
    }

    protected static function booted()
    {
        static::created(function ($detalle) {
            $detalle->entrega->update(['liquidada' => true]);
        });
    }

}
