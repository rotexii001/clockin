<?php

namespace Edscool\Plugins\ClockIn\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Edscool\Plugins\ClockIn\Models\StudentClockInRecord;
use Edscool\Plugins\ClockIn\Events\StudentClockedInEvent;
use Carbon\Carbon;

class ClockInController extends Controller
{
    /**
     * Display the teacher's classroom student roster with real-time clock-in status.
     */
    public function index(Request $request)
    {
        $user = auth('owner')->user() ?? auth('school_admin')->user() ?? auth('staff')->user() ?? auth()->user();
        $schoolId = session('active_school_id') 
            ?? (is_object($user?->school) ? $user->school->id : $user?->school)
            ?? $user?->school_id 
            ?? 1;

        $selectedClassId = $request->query('class_id');
        $today = now()->toDateString();

        // Fetch classrooms available in the school
        $classrooms = collect([]);
        if (class_exists(\App\Models\classroom::class)) {
            $classrooms = \App\Models\classroom::where('school_id', $schoolId)->get();
            if (!$selectedClassId && $classrooms->isNotEmpty()) {
                $selectedClassId = $classrooms->first()->id;
            }
        }

        // Fetch students in selected classroom
        $students = collect([]);
        if ($selectedClassId && class_exists(\App\Models\students::class)) {
            $students = \App\Models\students::where('school', $schoolId)
                ->where('class', $selectedClassId)
                ->select(['id', 'surname', 'othernames', 'admission_number', 'class', 'school'])
                ->orderBy('surname')
                ->get();
        }

        // Fetch today's clock-ins for this class
        $clockIns = StudentClockInRecord::where('school_id', $schoolId)
            ->where('clock_in_date', $today)
            ->get()
            ->keyBy('student_id');

        // Plugin settings
        $settings = $this->getPluginSettings($schoolId);

        return view('clockin::teacher-clockin', compact('classrooms', 'students', 'clockIns', 'selectedClassId', 'settings', 'today'));
    }

    /**
     * Handle teacher clicking "Clock In" for a student.
     */
    public function clockIn(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'class_id' => 'nullable|integer',
        ]);

        $user = auth('owner')->user() ?? auth('school_admin')->user() ?? auth('staff')->user() ?? auth()->user();
        $schoolId = session('active_school_id') 
            ?? (is_object($user?->school) ? $user->school->id : $user?->school)
            ?? $user?->school_id 
            ?? 1;

        $studentId = (int) $request->input('student_id');
        $classId = $request->input('class_id');
        $today = now()->toDateString();
        $now = now();

        // 1. Verify student exists in this school
        if (class_exists(\App\Models\students::class)) {
            $student = \App\Models\students::where('school', $schoolId)->find($studentId);
            if (!$student) {
                return back()->with('error', 'Student not found in your institution roster.');
            }
            $classId = $classId ?: $student->class;
        }

        // 2. Check if already clocked in today
        $existing = StudentClockInRecord::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('clock_in_date', $today)
            ->first();

        if ($existing) {
            return back()->with('info', 'Student is already clocked in for today.');
        }

        // 3. Determine if arrival is 'present' (on-time) or 'late'
        $settings = $this->getPluginSettings($schoolId);
        $lateThreshold = $settings['late_arrival_time'] ?? '08:15';
        $thresholdTime = Carbon::createFromTimeString($lateThreshold);

        $status = $now->greaterThan($thresholdTime) ? 'late' : 'present';

        // 4. Record clock-in
        $record = StudentClockInRecord::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'class_id' => $classId,
            'teacher_id' => auth()->id() ?? null,
            'clock_in_date' => $today,
            'clock_in_time' => $now->toTimeString(),
            'status' => $status,
            'method' => 'teacher_manual',
            'notes' => $request->input('notes'),
        ]);

        // 5. Fire domain event for integrations (e.g. WhatsApp alert)
        event(new StudentClockedInEvent($record));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Student successfully clocked in.',
                'record' => $record,
            ]);
        }

        return back()->with('success', 'Student successfully clocked in.');
    }

    /**
     * Handle teacher undoing a clock-in click.
     */
    public function undoClockIn(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
        ]);

        $user = auth('owner')->user() ?? auth('school_admin')->user() ?? auth('staff')->user() ?? auth()->user();
        $schoolId = session('active_school_id') 
            ?? (is_object($user?->school) ? $user->school->id : $user?->school)
            ?? $user?->school_id 
            ?? 1;

        $studentId = (int) $request->input('student_id');
        $today = now()->toDateString();

        $record = StudentClockInRecord::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('clock_in_date', $today)
            ->first();

        if ($record) {
            $record->delete();
            return back()->with('success', 'Clock-in entry removed.');
        }

        return back()->with('error', 'No active clock-in entry found to remove.');
    }

    /**
     * Retrieve per-school configuration from school_plugins table.
     */
    protected function getPluginSettings(int $schoolId): array
    {
        $defaultSettings = [
            'late_arrival_time' => '08:15',
            'allow_undo' => true,
        ];

        if (class_exists(\App\Models\SchoolPlugin::class) && class_exists(\App\Models\Plugin::class)) {
            $plugin = \App\Models\Plugin::whereIn('slug', ['student-clockin', 'edscool-student-clockin'])->first();
            if ($plugin) {
                $installed = \App\Models\SchoolPlugin::where('school_id', $schoolId)
                    ->where('plugin_id', $plugin->id)
                    ->first();
                if ($installed && is_array($installed->settings)) {
                    return array_merge($defaultSettings, $installed->settings);
                }
            }
        }

        return $defaultSettings;
    }
}
