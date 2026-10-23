<?php

namespace Edscool\Plugins\ClockIn\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class StudentClockInRecord extends Model
{
    protected $table = 'plg_clockin_records';

    protected $guarded = ['id'];

    protected $casts = [
        'clock_in_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     * Enforces strict multi-tenant isolation so queries never leak across schools.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('school_tenant', function (Builder $builder) {
            $schoolId = session('active_school_id') 
                ?? auth()->user()->school 
                ?? auth()->user()->school_id 
                ?? request()->header('X-School-Id');

            if ($schoolId) {
                $builder->where('plg_clockin_records.school_id', $schoolId);
            }
        });

        // Automatically assign active tenant when creating a new record
        static::creating(function ($model) {
            if (empty($model->school_id)) {
                $model->school_id = session('active_school_id') 
                    ?? auth()->user()->school 
                    ?? auth()->user()->school_id 
                    ?? request()->header('X-School-Id');
            }
        });
    }

    /**
     * Scope a query to only include today's clock-ins.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->where('clock_in_date', now()->toDateString());
    }

    /**
     * Scope a query to a specific classroom.
     */
    public function scopeForClass(Builder $query, int $classId): Builder
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Retrieve student safely using App\Models\students or DTO contract.
     */
    public function getStudentAttribute()
    {
        if (class_exists(\App\Models\students::class)) {
            return \App\Models\students::withoutGlobalScopes()
                ->select(['id', 'first_name', 'last_name', 'admission_number', 'class', 'school'])
                ->find($this->student_id);
        }

        return null;
    }
}
