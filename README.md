# Student Clock-In Attendance Plugin for Edscool

**Package ID:** `edscool-student-clockin`  
**Namespace:** `Edscool\Plugins\ClockIn\`  
**Version:** `1.0.0`  
**Author:** Rotimi Olaosebikan (`rotexii001@gmail.com`)  
**Category:** Academics & Grading  
**License:** MIT  

---

## 1. Overview
The **Student Clock-In Attendance** plugin provides an institutional arrival tracking system for Edscool schools. Classroom teachers can view their daily class roster and 1-click clock in students as they arrive at school.

- **Real-Time Arrival Tracking:** Records exact timestamps when students arrive.
- **Automated Late Detection:** Automatically flags arrivals as `present` (on-time) or `late` based on the school's configured cutoff time (e.g. `08:15 AM`).
- **Undo Grace Window:** Teachers can undo accidental clicks within a configurable grace window.
- **Decoupled Event Broadcasting:** Fires `StudentClockedInEvent` so notification plugins (e.g., WhatsApp, SMS) can dispatch arrival alerts to parents without coupling.

---

## 2. Edscool Architectural Compliance

This plugin adheres strictly to the **Edscool Developer Standards**:

| Rule | Compliance Implementation |
| :--- | :--- |
| **Unique Naming** | Globally unique ID `edscool-student-clockin` and isolated root namespace `Edscool\Plugins\ClockIn\`. |
| **Zero Core Alterations** | Creates its own isolated table `plg_clockin_records`. Never alters `students`, `schools`, or `classes`. |
| **Multi-Tenant Scoping** | All database queries are explicitly scoped to `school_id` via `StudentClockInRecord::booted()` global scope. |
| **Sanitized Contracts** | Reads student and classroom information through sanitized read-only contract fields (`id`, `first_name`, `last_name`, `admission_number`). |
| **Clean Rollback** | Includes full `down()` method in migrations dropping `plg_clockin_records` on uninstallation. |
| **No Prohibited Calls** | Zero calls to `env()`, `putenv()`, `exec()`, or raw unparameterized SQL. |

---

## 3. Database Schema

All data is stored in the dedicated, isolated table:

```sql
CREATE TABLE plg_clockin_records (
    id BIGSERIAL PRIMARY KEY,
    school_id BIGINT NOT NULL,
    student_id BIGINT NOT NULL,
    class_id BIGINT NULL,
    teacher_id BIGINT NULL,
    clock_in_date DATE NOT NULL,
    clock_in_time TIME NOT NULL,
    status VARCHAR(255) DEFAULT 'present',
    method VARCHAR(255) DEFAULT 'teacher_manual',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (school_id, student_id, clock_in_date)
);
```

---

## 4. Manifest (`plugin.json`)

```json
{
  "id": "edscool-student-clockin",
  "name": "Student Clock-In Attendance",
  "version": "1.0.0",
  "author": "Rotimi Olaosebikan",
  "category": "Academics & Grading",
  "service_provider": "Edscool\\Plugins\\ClockIn\\ClockInPluginServiceProvider",
  "permissions": [
    "students.read_basic",
    "classes.read",
    "attendance.record"
  ],
  "settings_schema": {
    "late_arrival_time": {
      "type": "string",
      "label": "Late Arrival Threshold (HH:MM)",
      "default": "08:15",
      "required": true
    },
    "allow_undo": {
      "type": "boolean",
      "label": "Allow teachers to undo clock-in within 30 minutes",
      "default": true
    }
  }
}
```

---

## 5. Domain Events for Integrations

When a student clocks in, the plugin dispatches:

```php
use Edscool\Plugins\ClockIn\Events\StudentClockedInEvent;

Event::listen(StudentClockedInEvent::class, function ($event) {
    // $event->studentId
    // $event->schoolId
    // $event->clockInTime
    // $event->status ('present' or 'late')
});
```

---

## 6. Directory Layout

```
clockin/
├── plugin.json                 # Package identity & settings schema
├── composer.json               # Namespaced PSR-4 autoloading
├── README.md                   # Full documentation
├── database/
│   └── migrations/
│       └── 2026_10_24_000000_create_plg_clockin_records_table.php
├── src/
│   ├── ClockInPluginServiceProvider.php
│   ├── Events/
│   │   └── StudentClockedInEvent.php
│   ├── Http/
│   │   └── Controllers/
│   │       └── ClockInController.php
│   └── Models/
│       └── StudentClockInRecord.php
├── resources/
│   └── views/
│       └── teacher-clockin.blade.php
└── routes/
    └── web.php
```
