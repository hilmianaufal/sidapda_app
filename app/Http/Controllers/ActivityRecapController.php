<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\ActivitySession;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Exports\ActivityRecapExport;
use Maatwebsite\Excel\Facades\Excel;

class ActivityRecapController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $activityId = $request->input('activity_id');
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');

        $activities = Activity::orderBy('order')->get();

        $selectedActivity = $activityId
            ? Activity::find($activityId)
            : $activities->first();

        $kelasList = Student::whereNotNull('kelas')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        $kamarList = Student::whereNotNull('kamar')
            ->distinct()
            ->orderBy('kamar')
            ->pluck('kamar');

        $studentsQuery = Student::query()
            ->where('is_active', true)
            ->when($kelas, fn ($q) => $q->where('kelas', $kelas))
            ->when($kamar, fn ($q) => $q->where('kamar', $kamar));

        $session = null;
        $attendances = collect();
        $absentStudents = collect();

        if ($selectedActivity) {
            $session = ActivitySession::where('activity_id', $selectedActivity->id)
                ->whereDate('started_at', $date)
                ->first();

            if ($session) {
                $attendances = ActivityAttendance::with('student')
                    ->where('activity_session_id', $session->id)
                    ->when($kelas, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('kelas', $kelas)))
                    ->when($kamar, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('kamar', $kamar)))
                    ->orderByDesc('scanned_at')
                    ->get();

                $presentIds = $attendances->pluck('student_id');

                $absentStudents = (clone $studentsQuery)
                    ->whereNotIn('id', $presentIds)
                    ->orderBy('name')
                    ->get();
            } else {
                $absentStudents = (clone $studentsQuery)
                    ->orderBy('name')
                    ->get();
            }
        }

        $totalStudents = (clone $studentsQuery)->count();
        $hadirCount = $attendances->where('status', 'hadir')->count();
        $terlambatCount = $attendances->where('status', 'terlambat')->count();
        $belumCount = max(0, $totalStudents - ($hadirCount + $terlambatCount));

        return view('activities.recap', compact(
            'date',
            'activityId',
            'kelas',
            'kamar',
            'kelasList',
            'kamarList',
            'activities',
            'selectedActivity',
            'session',
            'attendances',
            'absentStudents',
            'totalStudents',
            'hadirCount',
            'terlambatCount',
            'belumCount'
        ));
    }

    public function exportExcel(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $activityId = (int) $request->input('activity_id');
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');

        $activity = Activity::findOrFail($activityId);

        $filename = 'Rekap-Kegiatan-'.$activity->name.'-'.$date
            .($kelas ? '-Kelas-'.$kelas : '')
            .($kamar ? '-Kamar-'.$kamar : '')
            .'.xlsx';

        return Excel::download(
            new ActivityRecapExport(
                $date,
                $activity->id,
                $activity->name,
                $kelas,
                $kamar
            ),
            $filename
        );
    }

    public function markStatus(Request $request)
{
    $data = $request->validate([
        'activity_id' => ['required', 'exists:activities,id'],
        'student_id' => ['required', 'exists:students,id'],
        'date' => ['required', 'date'],
        'status' => ['required', 'in:izin,sakit,pulang'],
    ]);

    $activity = Activity::findOrFail($data['activity_id']);

    $startedAt = \Illuminate\Support\Carbon::parse($data['date'].' '.$activity->start_time);
    $endedAt = \Illuminate\Support\Carbon::parse($data['date'].' '.$activity->end_time);

    $session = ActivitySession::firstOrCreate(
        [
            'activity_id' => $activity->id,
            'started_at' => $startedAt,
        ],
        [
            'ended_at' => $endedAt,
            'status' => 'live',
        ]
    );

    ActivityAttendance::updateOrCreate(
        [
            'activity_session_id' => $session->id,
            'student_id' => $data['student_id'],
        ],
        [
            'scanned_at' => now(),
            'status' => $data['status'],
        ]
    );

    return back()->with('success', 'Status santri berhasil diperbarui.');
}
}