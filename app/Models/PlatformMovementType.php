<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMovementType extends Model
{
    protected $fillable = ['name', 'direction'];

    public function movements()
    {
        return $this->hasMany(PlatformMovement::class);
    }
}
