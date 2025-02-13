<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    // Nombre de la tabla en la base de datos
    protected $table = 'proveedores';

    // Campos permitidos para asignación masiva
    protected $fillable = [
        'nombrecompleto',
        'cedula',
        'direccion',
        'ciudad',
        'municipio',
        'celular',
        'credito_disponible', //NEW
    ];

    // Definir la relación con los préstamos
    public function prestamos()
    {
        return $this->hasMany(Prestamo::class); // Un proveedor puede tener muchos préstamos
    }

    // Definir la relación con las Entregas
    public function entregas()
    {
        return $this->hasMany(Entrega::class); // Un proveedor puede tener muchas Entregas
    }


    // Relación con el modelo Liquidacion
    public function liquidaciones()
    {
        return $this->hasMany(Liquidacion::class);
    }

}
