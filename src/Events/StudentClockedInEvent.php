<?php

namespace Edscool\Plugins\ClockIn\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Edscool\Plugins\ClockIn\Models\StudentClockInRecord;

class StudentClockedInEvent
{
    use Dispatchable, SerializesModels;

    public StudentClockInRecord $record;

    public int $studentId;
    public int $schoolId;
    public string $clockInTime;
    public string $status;

    /**
     * Create a new event instance.
     */
    public function __construct(StudentClockInRecord $record)
    {
        $this->record = $record;
        $this->studentId = $record->student_id;
        $this->schoolId = $record->school_id;
        $this->clockInTime = (string) $record->clock_in_time;
        $this->status = $record->status;
    }
}
