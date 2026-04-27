<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intervention extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'user_id',
        'category',
        'patient_gender',
        'patient_age',
        'gestures',
        'constants',
        'driving',
        'no_transport',
        'hospital_id',
    ];

    protected function casts(): array
    {
        return [
            'gestures'     => 'array',
            'constants'    => 'array',
            'no_transport' => 'boolean',
            'patient_age'  => 'integer',
        ];
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'intervention_items', 'intervention_id', 'article_id')
                    ->withPivot('quantity_used')
                    ->withTimestamps();
    }
}