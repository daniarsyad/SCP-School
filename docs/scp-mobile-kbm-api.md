# SCP Mobile KBM, Jurnal, Presensi, and Izin API

All endpoints below are under `/api` and require the existing Sanctum authentication unless noted. An account has one primary `users.role_id` role and may have additional roles through `user_additional_roles`. Student and parent accounts share an NIS but have independent passwords and tokens.

## Current backend and compatibility

- The backend is Laravel 13 / PHP 8.3, uses Sanctum, and stores schedules by teacher, class, weekday, and active cycle.
- `POST /api/login` accepts `identifier` plus `password`: students and parents use the student's shared NIS; Guru, Wali Kelas, Guru BK, and Waka use NIP. Existing clients may continue sending `username`. Parent accounts are created as a separate `Orang Tua` role through user management or an import row linked by class and student NIS; the response keeps role `Orang Tua` and includes `user.children`. Only an account whose primary role is `Waka Kurikulum` is blocked from mobile login. A Guru whose additional role is Waka can log in as Guru; the response `role` remains the primary role.
- Login `data.user` and `GET /api/me` `data` share these fields: `role` is the primary role name; `additional_roles` is an array of `{id, role_name}` objects containing only additional roles; `bk_classes` is an array of `{id, class_name, grade, major, section}` objects for every class related through `guru_bk_classes`. `bk_classes` is empty for accounts without the Guru BK role. These fields do not replace the existing `class_id`, `class`, or `children` fields. Class data is limited to the identifiers and labels needed by the mobile class selector.

Example profile fields (the login response nests this object under `data.user`; `/api/me` returns it under `data`):

```json
{
  "role": "Guru BK",
  "class_id": null,
  "class": null,
  "children": [],
  "additional_roles": [
    { "id": 1, "role_name": "Waka Kurikulum" }
  ],
  "bk_classes": [
    {
      "id": 5,
      "class_name": "X RPL 1",
      "grade": "X",
      "major": "RPL",
      "section": "1"
    },
    {
      "id": 6,
      "class_name": "X RPL 2",
      "grade": "X",
      "major": "RPL",
      "section": "2"
    }
  ]
}
```
- `GET /api/me` includes NIS/NIP and linked children. Student and parent clients should render the same student dashboard; parent data is scoped to the children in this response.
- `GET /api/dashboard/today` returns today's attendance totals and present percentage for the signed-in student, or linked children for a parent. Parents may pass `student_id` to select one linked child. The summary is calculated on each request using the Asia/Jakarta date.
- `POST /api/journals` previously created a journal and automatically marked every student in that class `Hadir`, except students with an approved leave request, which were set to `Izin`.
- The existing `attendances.status` enum already contains `Hadir`, `Izin`, `Sakit`, and `Alpha`. The old status-update endpoint accepted only three values; it now accepts all four.
- Existing routes remain available. Journal submission accepts the old payload (no `attendances`) and retains automatic attendance creation. The new combined payload is an additive extension of the same endpoint.
- Leave requests previously had only approval state and free-text reason. Existing rows migrate to `leave_type = Izin`, preserving their old effective status.

## Final journal and attendance submission

### `POST /api/journals`

Role: `Guru` or `Wali Kelas`, and only for a schedule assigned to that user. A Wali Kelas is additionally restricted to their assigned `class_id`.

Send multipart form data. The attendance array must contain exactly one entry for every `Siswa` in the scheduled class.

```text
Idempotency-Key: 7ed...unique-client-generated-value

schedule_id: 23
date: 2026-10-26                 # optional; defaults to application "today"
topic_description: Persamaan Linear
activity_notes: Diskusi dan latihan kelompok  # optional
photo: <jpg/png image, max 2 MiB>
attendances[0][student_id]: 100
attendances[0][status]: Hadir
attendances[1][student_id]: 101
attendances[1][status]: Sakit
```

Supported attendance statuses are exactly `Hadir`, `Izin`, `Sakit`, and `Alpha`. The request validates that the schedule belongs to the caller, is for the active cycle, its weekday matches `date`, and that any configured cycle date range contains that date. Dates in the future are rejected. Photos must be JPEG or PNG images and no larger than 2 MiB.

Successful first submission returns HTTP `201`; an identical retry with the same `Idempotency-Key` returns HTTP `200` and `replayed: true`.

```json
{
  "success": true,
  "message": "Jurnal berhasil dibuat dan presensi berhasil disinkronkan",
  "replayed": false,
  "data": {
    "id": 58,
    "schedule_id": 23,
    "date": "2026-10-26",
    "topic_description": "Persamaan Linear",
    "activity_notes": "Diskusi dan latihan kelompok",
    "photo_path": "journals/...",
    "attendances": [
      { "student_id": 100, "status": "Hadir" },
      { "student_id": 101, "status": "Sakit" }
    ]
  }
}
```

The database write for journal and attendance rows is one transaction. A unique journal-per-schedule/date constraint and a unique attendance-per-journal/student constraint prevent duplicate sessions and duplicate students. Supply the same idempotency key and payload on retry; reusing the key for different content returns HTTP `409`. A second submission for the same schedule/date also returns `409`.

Validation errors return HTTP `422`; unauthenticated requests return `401`; schedule ownership/class violations return `403`; missing IDs return `404`. The old no-attendance payload still gets attendance rows generated by the backend and remains subject to the same atomic transaction and journal uniqueness rule.

## Schedule and existing attendance endpoints

| Endpoint | Allowed role / scope | Contract |
|---|---|---|
| `POST /api/schedules` | Primary role `Waka Kurikulum` only | Creates a schedule for the active cycle. Subject validation is shared with web schedule entry. Guru BK may use `BK` or `Bimbingan dan Konseling` even when `users.subject` is empty. Validation errors return `422` with `errors.subject`; schedule conflicts return `409` with `conflict_with` details. |
| `GET /api/schedules/today` | Existing schedule scope: Guru sees own schedules; Wali Kelas and students see their class; parents see linked children’s classes; BK sees their own assigned schedules | Existing `{success, day, cycle, data}` response retained. |
| `GET /api/attendances/journal/{journalId}` | Guru or Wali Kelas who owns that journal’s schedule | Existing `{success, data}` response retained. |
| `POST /api/attendances/{attendanceId}/status` | Guru or Wali Kelas who owns the schedule | Existing request `{ "status": "Hadir" }`; now accepts `Hadir`, `Izin`, `Sakit`, `Alpha`. Approved leave status remains authoritative; this endpoint updates the underlying status restored if the leave is later cancelled. |
| `PUT /api/attendances/{attendanceId}` | Same authorization and contract as the POST status route | Retained for compatibility. |
| `GET /api/attendances/my` | Student or linked parent | Students receive only their own history. Parents may pass `student_id` to select a linked child; without it they receive history for all linked children. An unlinked student ID returns 403. Records are newest first and include attendance/journal dates as `YYYY-MM-DD`, status, student/class, and journal schedule subject. Missing attendance records remain absent; the endpoint does not generate `Alpha` records. |

## Class lookup

### `GET /api/classes`

This public endpoint returns classes for the mobile class selector and roster, including `grade`, `major`, and `section`. `students` contains only users with the `Siswa` role and includes `nis`.

```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "class_name": "X RPL 1",
      "homeroom_teacher": {
        "id": 12,
        "fullname": "Nama Wali Kelas"
      },
      "students": [
        {
          "id": 101,
          "fullname": "Nama Siswa"
        }
      ]
    }
  ]
}
```

## Attendance report and monitoring

### `GET /api/attendances/report`

Role: Wali Kelas (own class only) or Guru BK (cross-class; optional class filter).

Filters:

- `date=YYYY-MM-DD` for one day; omitted means application today.
- `month=YYYY-MM` for a calendar month.
- `from_date=YYYY-MM-DD&to_date=YYYY-MM-DD` for an inclusive date range.
- Optional `class_id` (a Wali Kelas may only request their own class), `grade` (`X`, `XI`, or `XII`), `major`, `section` (rombel), `teacher_id`, and `period_id` (useful for BK monitoring by lesson period).
- Do not combine `month` with date filters, or `date` with `from_date`/`to_date`.

The original `date` and `data` fields remain. The response adds `summary` and `student_summary` with counts by status, class roster size when a class is selected, percentage Hadir among recorded attendance rows, per-student totals, trailing and maximum calendar-day Alpha streaks, and `repeated_alpha` when any streak exceeds three days. Period and teacher/class filters apply before calculating summaries.

### `GET /api/attendances/report/filters`

Role: Wali Kelas, Guru BK, Waka Kurikulum, or Kepala Sekolah. Returns the available `classes`, `grades`, and `teachers` for report filter controls; Wali Kelas receives only their assigned class and teachers scheduled for that class.

Each attendance in `data` includes the homeroom teacher at `journal.schedule.class.homeroom_teacher`, using the same `{id, fullname}` shape as `/api/classes`; it is `null` when the class has no assigned homeroom teacher.

```json
{
  "success": true,
  "date": null,
  "from_date": "2026-10-01",
  "to_date": "2026-10-31",
  "summary": {
    "total_students": 32,
    "total_sessions": 320,
    "hadir": 300,
    "izin": 8,
    "sakit": 4,
    "alpha": 8,
    "present_percentage": 93.75
  },
  "student_summary": [
    {
      "student_id": 100,
      "total_sessions": 10,
      "hadir": 8,
      "izin": 1,
      "sakit": 0,
      "alpha": 1,
      "alpha_consecutive_days": 0,
      "max_alpha_consecutive_days": 1,
      "repeated_alpha": false
    }
  ],
  "data": []
}
```

These are totals over attendance rows for submitted journals. The API does not infer a missing journal as an absence or fabricate scheduled lesson counts.

### `GET /api/attendances/report/export`

Uses the same role authorization and filters as `/api/attendances/report`, then downloads an XLSX workbook with attendance details and summary/student-summary sheets. It reuses the already-installed PhpSpreadsheet package; no dependency was added. PDF export is not implemented because no PDF package or existing PDF export pattern is present.

### `GET /api/journals/monitor`

Role: Waka Kurikulum or Kepala Sekolah. Supports `date` or `from_date`/`to_date`, `class_id`, `grade`, `major`, `section`, and `teacher_id`. Returns existing journals only, with schedule/class/teacher, `submitted_at`, attendance count, and `attendance_complete`. It does not mark unsubmitted schedules overdue because there is no configured school-day/deadline rule. The current mobile login flow blocks Waka accounts; a Waka web/session or other existing authorized Sanctum authentication is required.

## Leave request synchronization

### Existing routes with additions

| Endpoint | Allowed role / scope | Contract |
|---|---|---|
| `GET /api/leave-requests/pending` | Wali Kelas sees only students in their `class_id`; BK sees pending requests across classes, read-only | Existing route and response retained. Cancelled requests are excluded. |
| `POST /api/leave-requests/{id}/verify` | Wali Kelas for the student’s class only | `{ "status_approval": "Approved" }` or `{ "status_approval": "Rejected", "rejection_reason": "..." }`. Rejection reason is required, 3–500 chars. BK and ordinary Guru receive `403`. |
| `POST /api/leave-requests` | Student or parent of the selected student | Existing request fields retained; optional `leave_type` is `Izin` or `Sakit` and defaults to `Izin` for old clients. |
| `GET /api/leave-requests/my` | Student or parent, scoped to self/linked children | Existing route retained; includes new leave type, rejection reason, and cancellation metadata. |
| `POST /api/leave-requests/{id}/cancel` | The applicant or the student named in the request | Cancels a pending or approved request. Response contains `cancelled_at` and `cancelled_by`. Existing `status_approval` is not rewritten, for compatibility; cancelled approvals are ignored by attendance synchronization. |

Approving a request updates existing attendance rows over its inclusive date range. If several approved requests overlap, `Sakit` takes precedence over `Izin`. Cancelling an approved request recalculates attendance from remaining approvals, or restores the pre-leave attendance value. New journals also apply any approved leave that covers their date. Leave synchronization and request approval/cancellation occur in a transaction.

## Migration and tests

Apply migrations in the normal deployment process:

```powershell
php artisan migrate --force
```

The migrations add nullable journal notes and idempotency fields, unique journal/date and attendance/student indexes, leave type/rejection fields, the nullable `leave_requests.cancelled_at` timestamp and its synchronization index, a table to preserve pre-leave attendance status, and nullable `users.nis`/`users.nip` identity columns. The cancellation timestamp is added by `2026_10_30_000001_add_cancelled_at_to_leave_requests`; its `down()` removes the field and index when this migration owns them, while the preceding migration handles rollback when both are rolled back together. NIS is unique per role, allowing a student's linked parent account to intentionally share the same NIS. The migrations deliberately do not delete or merge historical records. Existing users keep working with username until NIS/NIP are filled in through the user form/import.

Run the focused automated coverage:

```powershell
php artisan test tests/Feature/LeaveRequestCancellationMigrationTest.php tests/Feature/JournalAttendanceApiTest.php
```

Run the whole suite before release:

```powershell
php artisan test
```

The tests use Laravel’s in-memory SQLite testing database; they do not apply or validate the migration against the production database engine or production data.

## Flutter changes still required

1. Login sends `identifier` and `password`: siswa/orang tua enter the student's NIS; Guru, Wali Kelas, Guru BK, and Waka enter NIP. Keep `username` as a backward-compatible fallback for old accounts. Treat `role` as the primary role; `additional_roles` must not replace it or grant Waka-only mobile API access. A primary-role Waka remains website-only.
2. A parent uses the same NIS as the linked student but the separate password provisioned from the Excel import. Do not copy the student's password to the parent's local profile. Read `user.children` from login/`GET /api/me`; use its student ID when requesting `/api/dashboard/today?student_id=...`. Only show child records returned by the API.
3. Refresh today's dashboard data from `GET /api/dashboard/today` and attendance history from `GET /api/attendances/my`; handle zero records as 0% and do not cache the percentage beyond the normal short-lived API cache.
4. For attendance recap controls, load options from `GET /api/attendances/report/filters`; send `date` or a date range plus the selected `class_id`, `grade`, `major`, `section`, `teacher_id`, and optional `period_id` to `/api/attendances/report`. Do not send both `date` and `from_date`/`to_date`.
5. Journal monitoring accepts `date` or a date range and class/grade/major/section/teacher filters on `/api/journals/monitor`; it remains restricted to Waka/Kepala Sekolah roles. Do not assume unsubmitted journals are present in this endpoint.
6. Keep the existing local attendance draft exclusively on-device. Do not call any attendance or journal endpoint while the teacher is editing that draft. On “Selesai & Simpan Jurnal”, send one multipart `POST /api/journals` with `schedule_id`, `topic_description`, optional `activity_notes`, required journal photo, and one `attendances[][student_id,status]` item per class student.
7. Generate an idempotency key once for that finalized draft, persist it locally until a successful response, and reuse it with the unchanged payload on retries. Treat `201` and an idempotent `200` as success; handle `409` as an already-submitted/duplicate conflict and `422` by showing field errors.
8. Keep exact backend status enum spellings (`Hadir`, `Izin`, `Sakit`, `Alpha`). Expose `leave_type` when submitting leave, show `rejection_reason`, and allow cancellation only while the request API permits it. BK users should see pending requests as read-only.

## Known limitations and decisions

- The API exposes additional role names and Guru BK class assignments for profile display and role-aware mobile behavior. Waka-specific API actions remain restricted to primary-role Waka accounts; `additional_roles` does not grant those permissions.
- `Kepala Sekolah` is not among the seeded roles. The monitoring authorization recognizes that exact role name if provisioned, but a role/account provisioning and login policy must be agreed before enabling it.
- The project has no leave type or approval-history audit log. A `leave_type` field and rejection/cancellation metadata are added; prior attendance value is kept only to safely undo leave status. This is not a full immutable audit trail of every attendance edit.
- The project configures `UTC` as its application timezone. Alpha streaks currently mean consecutive calendar dates in that configured timezone; public holidays and instructional-day calendars are not modeled.
- XLSX export is implemented using the already-installed PhpSpreadsheet package. PDF generation remains separate work because the project has no suitable PDF package or export pattern.
- Journal photographs continue using the existing `public` filesystem disk because existing website views link to `public/storage`. The upload endpoint validates image content and size, but serving existing public-disk files is not role-gated; deployments requiring confidential photos need a separately planned private-storage migration and authenticated download route.
