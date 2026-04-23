<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'category',
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
        return $this->belongsToMany(Intervention::class, 'intervention_items', 'article_id', 'intervention_id')
                    ->withPivot('quantity_used')
                    ->withTimestamps();
    }

    public function isExpiringSoon(): bool
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
        return $this->quantity > 0 && $this->quantity < $this->max_quantity;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity === 0;
    }
}