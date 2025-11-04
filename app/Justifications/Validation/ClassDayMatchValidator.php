<?php

namespace App\Justifications\Validation;

use App\Models\ClassGroup;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ClassDayMatchValidator extends AbstractValidationHandler
{
    protected function validate(array $payload): void
    {
        $classId   = $payload['university_class_id'] ?? null;
        $startDate = $payload['start_date'] ?? null;
        $endDate   = $payload['end_date'] ?? null;

        if (!$classId || !$startDate || !$endDate) {
            return;
        }

        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        // Collect day-of-week integers within the inclusive range.
        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->dayOfWeek; // 0..6
        }
        $days = array_unique($days);

        $hasValidDays = ClassGroup::where('class_id', $classId)
            ->whereHas('days', fn($q) => $q->whereIn('weekday', $days))
            ->exists();

        if (!$hasValidDays) {
            throw ValidationException::withMessages([
                'university_class_id' => 'La clase seleccionada no tiene horarios en las fechas indicadas.',
            ]);
        }
    }
}
