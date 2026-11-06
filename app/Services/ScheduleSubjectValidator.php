<?php

namespace App\Services;

use App\Models\User;

class ScheduleSubjectValidator
{
    public function errorFor(User $teacher, string $subject): ?string
    {
        $teacherSubject = trim((string) $teacher->subject);
        $subject = trim($subject);
        $normalizedSubject = preg_replace('/[^a-z]/u', '', mb_strtolower($subject));

        if ($teacher->isGuruBK() && in_array($normalizedSubject, [
            'bk',
            'bimbingankonseling',
            'bimbingandankonseling',
        ], true)) {
            return null;
        }

        if ($teacherSubject === '') {
            return "Kolom Pengajar / Mata Pelajaran: {$teacher->fullname} belum memiliki mata pelajaran pada data pengguna.";
        }

        if ($subject === '' || $subject === '---') {
            return 'Kolom Mata Pelajaran: mata pelajaran tidak boleh kosong atau berupa strip.';
        }

        if (strcasecmp($teacherSubject, $subject) === 0) {
            return null;
        }

        $normalizedSubject = $this->normalizeSubjectString($subject);
        $delimiter = str_contains($teacherSubject, ';') ? ';' : (str_contains($teacherSubject, ',') ? ',' : null);
        $teacherSubjects = $delimiter
            ? array_map('trim', explode($delimiter, $teacherSubject))
            : [$teacherSubject];

        foreach ($teacherSubjects as $teacherSubjectPart) {
            if ($teacherSubjectPart === '') {
                continue;
            }

            if (strcasecmp($teacherSubjectPart, $subject) === 0) {
                return null;
            }

            $normalizedTeacherSubject = $this->normalizeSubjectString($teacherSubjectPart);
            if (strcasecmp($normalizedTeacherSubject, $normalizedSubject) === 0) {
                return null;
            }

            $cleanSubject = trim(str_replace([')', '('], '', $normalizedSubject));
            if (mb_strlen($cleanSubject) >= 5 && mb_stripos($normalizedTeacherSubject, $cleanSubject) !== false) {
                return null;
            }
        }

        return "Kolom Pengajar / Mata Pelajaran: {$teacher->fullname} mengajar \"{$teacherSubject}\", bukan \"{$subject}\".";
    }

    private function normalizeSubjectString(string $subject): string
    {
        $normalized = trim($subject);
        $normalized = rtrim($normalized, ']');
        $normalized = preg_replace('/^(?:senin|selasa|rabu|kamis|jumat)\s+/iu', '', $normalized);
        $normalized = preg_replace('/\s*\/\s*/', '/', $normalized);
        $normalized = preg_replace('/\(\s+/', '(', $normalized);
        $normalized = preg_replace('/\s+\)/', ')', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = preg_replace('/\bphyton\b/iu', 'Python', $normalized);
        $normalized = preg_replace('/\bthinks\b/iu', 'Things', $normalized);
        $normalized = preg_replace('/\bbisnid\b/iu', 'Bisnis', $normalized);
        $normalized = preg_replace('/\bDDK PM\b/iu', 'DDPK PM', $normalized);

        return trim($normalized);
    }
}
