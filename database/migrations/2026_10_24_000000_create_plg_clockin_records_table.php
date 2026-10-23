<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table is isolated with mandatory prefix: plg_clockin_*
     */
    public function up(): void
    {
        if (!Schema::hasTable('plg_clockin_records')) {
            Schema::create('plg_clockin_records', function (Blueprint $table) {
                $table->id();

                // Multi-Tenant Isolation Key (REQUIRED)
                $table->unsignedBigInteger('school_id')->index();

                // Relationship references to core entities
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('class_id')->nullable()->index();
                $table->unsignedBigInteger('teacher_id')->nullable()->index();

                // Clock-In Data
                $table->date('clock_in_date')->index();
                $table->time('clock_in_time');
                $table->string('status')->default('present'); // present, late, excused
                $table->string('method')->default('teacher_manual'); // teacher_manual, rfid_kiosk, guardian_dropoff
                $table->string('notes')->nullable();

                $table->timestamps();

                // Compound indexes for tenant-scoped daily queries
                $table->unique(['school_id', 'student_id', 'clock_in_date'], 'plg_clockin_unique_daily_student');
                $table->index(['school_id', 'clock_in_date'], 'plg_clockin_school_date_idx');
                $table->index(['school_id', 'class_id', 'clock_in_date'], 'plg_clockin_class_date_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     * Cleanly drops the table on uninstallation without leaving residue on the host.
     */
    public function down(): void
    {
        Schema::dropIfExists('plg_clockin_records');
    }
};
