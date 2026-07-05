<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardapioCategory extends Model
{
    protected $fillable = ['name', 'cozinha', 'sort_order'];

    protected $casts = ['cozinha' => 'boolean'];
}
