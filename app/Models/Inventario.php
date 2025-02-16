<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventarios';

    protected $fillable = [
        'fecha',
        'cantidad_sacos',
        'peso_neto',
        'tipo_cafe',
        'humedad',
    ];

    /**
     * Relación con la Entrega.
     * Esto permite acceder a la información de la entrega asociada a este registro de inventario.
     */
    public function entrega()
    {
        return $this->belongsTo(Entrega::class);
    }

}
