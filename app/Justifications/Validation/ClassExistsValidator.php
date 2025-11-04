<?php

namespace App\Justifications\Validation;

use App\Models\UniversityClass;
use Illuminate\Validation\ValidationException;

class ClassExistsValidator extends AbstractValidationHandler
{
    protected function validate(array $payload): void
    {
        $classId = $payload['university_class_id'] ?? null;

        if (!$classId || !UniversityClass::query()->whereKey($classId)->exists()) {
            throw ValidationException::withMessages([
                'university_class_id' => 'La clase seleccionada no existe.',
            ]);
        }
    }
}
