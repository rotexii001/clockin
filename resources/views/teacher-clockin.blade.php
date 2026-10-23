<div class="clockin-container" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif; padding: 24px; max-width: 1200px; margin: 0 auto;">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 20px; margin-bottom: 8px;">
                <i class="fas fa-clock"></i> Daily Arrival Monitor
            </div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0;">Student Clock-In Attendance</h1>
            <p style="color: #64748b; font-size: 13.5px; margin: 4px 0 0 0;">
                Today: <strong>{{ \Carbon\Carbon::parse($today)->format('l, F j, Y') }}</strong> &bull; Cutoff for on-time arrival: <strong>{{ $settings['late_arrival_time'] ?? '08:15' }} AM</strong>
            </p>
        </div>

        <!-- Classroom Selector -->
        <div>
            <form method="GET" action="{{ route('plugins.clockin.index') }}" style="display: flex; gap: 8px; align-items: center;">
                <label for="class_select" style="font-size: 13px; font-weight: 700; color: #334155;">Classroom:</label>
                <select id="class_select" name="class_id" onchange="this.form.submit()" style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; color: #0f172a; background: #ffffff;">
                    @forelse($classrooms as $cls)
                        <option value="{{ $cls->id }}" {{ $selectedClassId == $cls->id ? 'selected' : '' }}>
                            {{ $cls->class_name ?? $cls->name ?? 'Class #' . $cls->id }}
                        </option>
                    @empty
                        <option value="">No classrooms found</option>
                    @endforelse
                </select>
            </form>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Metric Counters -->
    @php
        $totalStudents = count($students);
        $clockedCount = count($clockIns);
        $pendingCount = max(0, $totalStudents - $clockedCount);
        $lateCount = $clockIns->where('status', 'late')->count();
    @endphp

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase;">Total Class Roster</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $totalStudents }}</div>
        </div>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="color: #059669; font-size: 12px; font-weight: 700; text-transform: uppercase;">Clocked In (Present)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #059669; margin-top: 4px;">{{ $clockedCount }}</div>
        </div>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="color: #d97706; font-size: 12px; font-weight: 700; text-transform: uppercase;">Late Arrivals</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #d97706; margin-top: 4px;">{{ $lateCount }}</div>
        </div>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase;">Not Yet Arrived</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #64748b; margin-top: 4px;">{{ $pendingCount }}</div>
        </div>
    </div>

    <!-- Student Roster Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
        <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-weight: 800; font-size: 14px; color: #0f172a;">Student Roster</div>
            <div style="font-size: 12px; color: #64748b;">Teachers can click to clock in student arrival</div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
                <thead>
                    <tr style="background: #ffffff; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 11.5px; text-transform: uppercase;">
                        <th style="padding: 12px 20px;">#</th>
                        <th style="padding: 12px 20px;">Student Name</th>
                        <th style="padding: 12px 20px;">Admission No.</th>
                        <th style="padding: 12px 20px;">Today's Arrival Status</th>
                        <th style="padding: 12px 20px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $index => $student)
                        @php
                            $isClocked = isset($clockIns[$student->id]);
                            $clockRecord = $isClocked ? $clockIns[$student->id] : null;
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9; background: {{ $isClocked ? '#fcfdfd' : '#ffffff' }};">
                            <td style="padding: 14px 20px; color: #94a3b8; font-weight: 600;">
                                {{ $index + 1 }}
                            </td>
                            <td style="padding: 14px 20px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 34px; height: 34px; border-radius: 50%; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12.5px;">
                                        {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a;">{{ $student->first_name }} {{ $student->last_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 20px; color: #475569; font-family: monospace;">
                                {{ $student->admission_number ?? 'EDS/' . $student->id }}
                            </td>
                            <td style="padding: 14px 20px;">
                                @if($isClocked)
                                    @if($clockRecord->status === 'late')
                                        <span style="display: inline-flex; align-items: center; gap: 5px; background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                                            <i class="fas fa-triangle-exclamation"></i> Late ({{ \Carbon\Carbon::parse($clockRecord->clock_in_time)->format('h:i A') }})
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                                            <i class="fas fa-circle-check"></i> Clocked In ({{ \Carbon\Carbon::parse($clockRecord->clock_in_time)->format('h:i A') }})
                                        </span>
                                    @endif
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 5px; background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                        <i class="fas fa-hourglass-start"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 14px 20px; text-align: right;">
                                @if(!$isClocked)
                                    <form method="POST" action="{{ route('plugins.clockin.clockin') }}" style="display: inline;">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                                        <button type="submit" style="background: #1680fb; color: #ffffff; border: none; padding: 7px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 2px 6px rgba(22, 128, 251, 0.25);">
                                            <i class="fas fa-user-check" style="margin-right: 4px;"></i> Clock In
                                        </button>
                                    </form>
                                @else
                                    <div style="display: inline-flex; align-items: center; gap: 10px;">
                                        <span style="color: #10b981; font-weight: 700; font-size: 12.5px;">
                                            <i class="fas fa-check"></i> Recorded
                                        </span>
                                        @if($settings['allow_undo'] ?? true)
                                            <form method="POST" action="{{ route('plugins.clockin.undo') }}" style="display: inline;" onsubmit="return confirm('Undo this clock-in record?');">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <button type="submit" style="background: transparent; color: #94a3b8; border: 1px solid #e2e8f0; padding: 3px 8px; border-radius: 6px; font-size: 11px; cursor: pointer;">
                                                    Undo
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 40px 20px; text-align: center; color: #94a3b8;">
                                <i class="fas fa-users-slash" style="font-size: 28px; margin-bottom: 10px; display: block; color: #cbd5e1;"></i>
                                No students found for this classroom. Select another class above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
