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
            $school = session('active_school_id');
            if (!$school) {
                $user = auth('owner')->user() ?? auth('school_admin')->user() ?? auth('staff')->user() ?? auth()->user();
                if ($user) {
                    if (!empty($user->school_id)) {
                        $school = $user->school_id;
                    } elseif (!empty($user->school)) {
                        $s = $user->school;
                        if ($s instanceof \Illuminate\Support\Collection) {
                            $school = optional($s->first())->id;
                        } elseif (is_object($s)) {
                            $school = $s->id ?? null;
                        } else {
                            $school = $s;
                        }
                    }
                }
            }

            if ($school) {
                $builder->where('plg_clockin_records.school_id', (int) $school);
            }
        });

        // Automatically assign active tenant when creating a new record
        static::creating(function ($model) {
            if (empty($model->school_id)) {
                $school = session('active_school_id');
                if (!$school) {
                    $user = auth('owner')->user() ?? auth('school_admin')->user() ?? auth('staff')->user() ?? auth()->user();
                    if ($user) {
                        if (!empty($user->school_id)) {
                            $school = $user->school_id;
                        } elseif (!empty($user->school)) {
                            $s = $user->school;
                            if ($s instanceof \Illuminate\Support\Collection) {
                                $school = optional($s->first())->id;
                            } elseif (is_object($s)) {
                                $school = $s->id ?? null;
                            } else {
                                $school = $s;
                            }
                        }
                    }
                }
                $model->school_id = (int) ($school ?? 1);
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
