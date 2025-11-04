<?php

namespace App\Justifications\Validation;

interface JustificationValidationHandler
{
    public function handle(array $payload): void;

    public function setNext(?JustificationValidationHandler $next): JustificationValidationHandler;
}
