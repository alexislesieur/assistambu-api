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
        'categorie',
        'patient_gender',
        'patient_age',
        'gestes',
        'conduite',
        'no_transport',
        'hospital_id',
    ];

    protected function casts(): array
    {
        return [
            'gestes'         => 'array',
            'no_transport' => 'boolean',
            'patient_age'    => 'integer',
        ];
    }

    public function garde()
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
        return $this->belongsToMany(Item::class, 'intervention_items')
                    ->withPivot('quantite_utilisee')
                    ->withTimestamps();
    }
}