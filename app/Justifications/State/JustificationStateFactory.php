<?php

namespace App\Justifications\State;

use App\Models\Justification;
use InvalidArgumentException;

class JustificationStateFactory
{
    public static function make(string $status): JustificationState
    {
        return match ($status) {
            Justification::STATUS_PENDING  => new SubmittedState(),
            Justification::STATUS_APPROVED => new ApprovedState(),
            Justification::STATUS_REJECTED => new RejectedState(),
            default => throw new InvalidArgumentException('Estado de justificación no soportado: '.$status),
        };
    }
}
