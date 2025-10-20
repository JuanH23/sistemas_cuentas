<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $fillable = ['name', 'icon'];

    public function movements()
    {
        return $this->hasMany(PlatformMovement::class);
    }
}
