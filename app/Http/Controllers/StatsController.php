<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    // Stats par jour
    public function day(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $shifts = Shift::where('user_id', $request->user()->id)
            ->whereDate('started_at', $request->date)
            ->whereNotNull('ended_at')
            ->get();

        return response()->json($this->buildStats($shifts));
    }

    // Stats par semaine
    public function week(Request $request)
    {
        $request->validate([
            'week' => 'required|integer|min:1|max:53',
            'year' => 'required|integer|min:2020',
        ]);

        $shifts = Shift::where('user_id', $request->user()->id)
            ->whereNotNull('ended_at')
            ->whereYear('started_at', $request->year)
            ->whereRaw('WEEK(started_at, 1) = ?', [$request->week])
            ->get();

        return response()->json($this->buildStats($shifts));
    }

    // Stats par mois
    public function month(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020',
        ]);

        $shifts = Shift::where('user_id', $request->user()->id)
            ->whereNotNull('ended_at')
            ->whereMonth('started_at', $request->month)
            ->whereYear('started_at', $request->year)
            ->get();

        return response()->json($this->buildStats($shifts));
    }

    // Construire les stats à partir d'une collection de gardes
    private function buildStats($shifts): array
    {
        $totalAmplitudeMinutes = $shifts->sum(fn($s) => $s->amplitudeMinutes() ?? 0);
        $totalTteMinutes       = $shifts->sum(fn($s) => $s->tteMinutes() ?? 0);
        $totalBreakMinutes     = $shifts->sum(fn($s) => $s->break_minutes ?? 0);
        $shiftCount            = $shifts->count();

        return [
            'shift_count'            => $shiftCount,
            'total_amplitude'        => $this->formatMinutes($totalAmplitudeMinutes),
            'total_amplitude_minutes'=> $totalAmplitudeMinutes,
            'total_tte'              => $this->formatMinutes($totalTteMinutes),
            'total_tte_minutes'      => $totalTteMinutes,
            'total_break'            => $this->formatMinutes($totalBreakMinutes),
            'total_break_minutes'    => $totalBreakMinutes,
            'average_amplitude'      => $shiftCount > 0 ? $this->formatMinutes(intdiv($totalAmplitudeMinutes, $shiftCount)) : null,
            'average_tte'            => $shiftCount > 0 ? $this->formatMinutes(intdiv($totalTteMinutes, $shiftCount)) : null,
            'shifts'                 => $shifts->map(fn($s) => [
                'id'                => $s->id,
                'started_at'        => $s->started_at,
                'ended_at'          => $s->ended_at,
                'driver'            => $s->driver,
                'break_minutes'     => $s->break_minutes,
                'amplitude'         => $s->amplitudeFormatted(),
                'amplitude_minutes' => $s->amplitudeMinutes(),
                'tte'               => $s->tteFormatted(),
                'tte_minutes'       => $s->tteMinutes(),
            ]),
        ];
    }

    // Formater des minutes en heures:minutes
    private function formatMinutes(int $minutes): string
    {
        return sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60);
    }
}