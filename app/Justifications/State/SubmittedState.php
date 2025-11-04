<?php

namespace App\Justifications\State;

use App\Models\Justification;
use App\Models\JustificationDocument;
use DateTimeInterface;
use LogicException;

class SubmittedState implements JustificationState
{
    public function approve(Justification $justification): void
    {
        $justification->update(['status' => Justification::STATUS_APPROVED]);
    }

    public function attachEvidence(Justification $justification, JustificationDocument $document): void
    {
        // Permitir adjuntar evidencia mientras está en proceso
        if (!$document->exists) {
            $justification->documents()->save($document);
        } else if ($document->justification_id !== $justification->id) {
            throw new LogicException('El documento pertenece a otra justificación.');
        }
    }

    public function comment(Justification $justification, string $comment): void
    {
        // No hay columna para comentarios; aquí podríamos emitir eventos/logs.
        // Por ahora, no-op para mantener la compatibilidad.
    }

    public function reject(Justification $justification): void
    {
        $justification->update(['status' => Justification::STATUS_REJECTED]);
    }

    public function scheduleReevaluation(Justification $justification, DateTimeInterface $when): void
    {
        // Ya está en proceso; podríamos registrar una fecha de revisión si existiera un campo.
    }

    public function submit(): void
    {
        // Ya está enviada/En Proceso; no hace nada.
    }
}
