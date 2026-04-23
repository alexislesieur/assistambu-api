<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
        'driver',
        'break_minutes',
    ];

    protected function casts(): array
    {
        return [
            'started_at'    => 'datetime',
            'ended_at'      => 'datetime',
            'driver'        => 'boolean',
            'break_minutes' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function interventions()
    {
        return $this->hasMany(Intervention::class);
    }

    public function isOngoing(): bool
    {
        return $this->ended_at === null;
    }

    // Amplitude en minutes
    public function amplitudeMinutes(): ?int
    {
        if (!$this->ended_at) return null;
        return $this->started_at->diffInMinutes($this->ended_at);
    }

    // TTE en minutes (amplitude - pauses)
    public function tteMinutes(): ?int
    {
        $amplitude = $this->amplitudeMinutes();
        if ($amplitude === null) return null;
        return $amplitude - ($this->break_minutes ?? 0);
    }

    // Amplitude formatée en heures:minutes
    public function amplitudeFormatted(): ?string
    {
        $minutes = $this->amplitudeMinutes();
        if ($minutes === null) return null;
        return sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60);
    }

    // TTE formaté en heures:minutes
    public function tteFormatted(): ?string
    {
        $minutes = $this->tteMinutes();
        if ($minutes === null) return null;
        return sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60);
    }
}