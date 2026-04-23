<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'categorie',
        'quantity',
        'max_quantity',
        'dlc',
    ];

    protected function casts(): array
    {
        return [
            'dlc'          => 'date',
            'quantity'     => 'integer',
            'max_quantity' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function interventions()
    {
        return $this->belongsToMany(Intervention::class, 'intervention_items')
                    ->withPivot('quantite_utilisee')
                    ->withTimestamps();
    }

    public function isExpiringsSoon(): bool
    {
        if (!$this->dlc) return false;
        return $this->dlc->diffInDays(now()) <= 30;
    }

    public function isExpired(): bool
    {
        if (!$this->dlc) return false;
        return $this->dlc->isPast();
    }

    public function isLowStock(): bool
    {
        return $this->quantite > 0 && $this->quantite < $this->quantite_max;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantite === 0;
    }
}