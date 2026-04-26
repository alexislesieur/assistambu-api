<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterventionCategory extends Model
{
    protected $table = 'intervention_categories';

    protected $fillable = [
        'name',
        'color',
        'bg',
        'active',
        'order',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}