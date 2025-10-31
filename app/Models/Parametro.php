<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametro extends Model
{
    protected $table = 'parametro';

    protected $fillable = [
        'clave', 'valor', 'descripcion', 'estado',
    ];

    public static function getValor(string $clave, $default = null)
    {
        return static::where('clave', $clave)->value('valor') ?? $default;
    }
}
