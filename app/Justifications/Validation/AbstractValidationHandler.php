<?php

namespace App\Justifications\Validation;

abstract class AbstractValidationHandler implements JustificationValidationHandler
{
    protected ?JustificationValidationHandler $next = null;

    public function setNext(?JustificationValidationHandler $next): JustificationValidationHandler
    {
        $this->next = $next;
        return $next ?? $this; // allow fluent wiring
    }

    public function handle(array $payload): void
    {
        $this->validate($payload);

        if ($this->next) {
            $this->next->handle($payload);
        }
    }

    abstract protected function validate(array $payload): void;
}
