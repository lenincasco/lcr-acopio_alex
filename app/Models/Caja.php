<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
  protected $fillable = [
    'fecha',
    'tipo',
    'concepto',
    'monto',
    'referencia',
    'user_id',
    'estado',
    'dias_diff',
    'qq_abonados',
  ];
}

