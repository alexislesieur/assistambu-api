<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Shift;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    // Lister tout le planning de l'utilisateur
    public function index(Request $request)
    {
        $schedules = Schedule::where('user_id', $request->user()->id)
            ->orderBy('date')
            ->get();

        return response()->json($schedules);
    }

    // Lister le planning par mois avec stats
    public function byMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020',
        ]);

        $schedules = Schedule::where('user_id', $request->user()->id)
            ->whereMonth('date', $request->month)
            ->whereYear('date', $request->year)
            ->orderBy('date')
            ->get();

        $shifts = Shift::where('user_id', $request->user()->id)
            ->whereNotNull('ended_at')
            ->whereMonth('started_at', $request->month)
            ->whereYear('started_at', $request->year)
            ->get();

        return response()->json([
            'schedules' => $schedules,
            'stats'     => $this->buildStats($shifts),
        ]);
    }

    // Lister le planning par semaine avec stats
    public function byWeek(Request $request)
    {
        $request->validate([
            'week' => 'required|integer|min:1|max:53',
            'year' => 'required|integer|min:2020',
        ]);

        $schedules = Schedule::where('user_id', $request->user()->id)
            ->whereYear('date', $request->year)
            ->whereRaw('WEEK(date, 1) = ?', [$request->week])
            ->orderBy('date')
            ->get();

        $shifts = Shift::where('user_id', $request->user()->id)
            ->whereNotNull('ended_at')
            ->whereYear('started_at', $request->year)
            ->whereRaw('WEEK(started_at, 1) = ?', [$request->week])
            ->get();

        return response()->json([
            'schedules' => $schedules,
            'stats'     => $this->buildStats($shifts),
        ]);
    }

    // Créer un créneau
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|in:uph_night,uph_day,transport,training,day_off,vacation',
            'date'         => 'required|date',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i',
        ]);

        // Vérifier qu'il n'y a pas déjà un créneau ce jour
        $existing = Schedule::where('user_id', $request->user()->id)
            ->where('date', $validated['date'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Un créneau existe déjà pour cette date.',
            ], 422);
        }

        $schedule = Schedule::create([
            'user_id'      => $request->user()->id,
            'service_type' => $validated['service_type'],
            'date'         => $validated['date'],
            'start_time'   => $validated['start_time'],
            'end_time'     => $validated['end_time'],
        ]);

        return response()->json($schedule, 201);
    }

    // Afficher un créneau
    public function show(Request $request, Schedule $schedule)
    {
        if ($schedule->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json($schedule);
    }

    // Modifier un créneau
    public function update(Request $request, Schedule $schedule)
    {
        if ($schedule->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'service_type' => 'sometimes|in:uph_night,uph_day,transport,training,day_off,vacation',
            'date'         => 'sometimes|date',
            'start_time'   => 'sometimes|date_format:H:i',
            'end_time'     => 'sometimes|date_format:H:i',
        ]);

        $schedule->update($validated);

        return response()->json($schedule);
    }

    // Supprimer un créneau
    public function destroy(Request $request, Schedule $schedule)
    {
        if ($schedule->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $schedule->delete();

        return response()->json(['message' => 'Créneau supprimé.']);
    }

    // Construire les stats à partir d'une collection de gardes
    private function buildStats($shifts): array
    {
        $totalAmplitudeMinutes = $shifts->sum(fn($s) => $s->amplitudeMinutes() ?? 0);
        $totalTteMinutes       = $shifts->sum(fn($s) => $s->tteMinutes() ?? 0);
        $totalBreakMinutes     = $shifts->sum(fn($s) => $s->break_minutes ?? 0);
        $shiftCount            = $shifts->count();

        return [
            'shift_count'             => $shiftCount,
            'total_amplitude'         => $this->formatMinutes($totalAmplitudeMinutes),
            'total_amplitude_minutes' => $totalAmplitudeMinutes,
            'total_tte'               => $this->formatMinutes($totalTteMinutes),
            'total_tte_minutes'       => $totalTteMinutes,
            'total_break'             => $this->formatMinutes($totalBreakMinutes),
            'total_break_minutes'     => $totalBreakMinutes,
            'average_amplitude'       => $shiftCount > 0 ? $this->formatMinutes(intdiv($totalAmplitudeMinutes, $shiftCount)) : null,
            'average_tte'             => $shiftCount > 0 ? $this->formatMinutes(intdiv($totalTteMinutes, $shiftCount)) : null,
        ];
    }

    // Formater des minutes en heures:minutes
    private function formatMinutes(int $minutes): string
    {
        return sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60);
    }
}