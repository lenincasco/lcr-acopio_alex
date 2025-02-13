<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'nombrecompleto',
        'cedula',
        'direccion',
        'ciudad',
        'municipio',
        'celular',
    ];


    // Definir la relación con las Entregas
    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
