<?php

namespace App\Justifications\State;

use App\Models\Justification;
use App\Models\JustificationDocument;
use DateTimeInterface;
use LogicException;

class RejectedState implements JustificationState
{
    public function approve(Justification $justification): void
    {
        // Posible tras apelación
        $justification->update(['status' => Justification::STATUS_APPROVED]);
    }

    public function attachEvidence(Justification $justification, JustificationDocument $document): void
    {
        // No permitimos adjuntar en rechazadas; requeriría re-evaluación
        throw new LogicException('No se puede adjuntar evidencia a una justificación rechazada.');
    }

    public function comment(Justification $justification, string $comment): void
    {
        // no-op
    }

    public function reject(Justification $justification): void
    {
        // Ya está rechazada; no-op
    }

    public function scheduleReevaluation(Justification $justification, DateTimeInterface $when): void
    {
        $justification->update(['status' => Justification::STATUS_PENDING]);
    }

    public function submit(): void
    {
        // no-op
    }
}
