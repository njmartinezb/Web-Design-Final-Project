<?php

namespace App\Justifications\Validation;

class JustificationValidationPipeline
{
    public static function makeForCreate(): JustificationValidationHandler
    {
        $head = new ClassExistsValidator();
        $head
            ->setNext(new ClassDayMatchValidator())
            ->setNext(new DocumentsValidator(required: true));

        return $head;
    }

    public static function makeForUpdate(): JustificationValidationHandler
    {
        $head = new ClassExistsValidator();
        $head
            ->setNext(new ClassDayMatchValidator())
            ->setNext(new DocumentsValidator(required: false));

        return $head;
    }
}
