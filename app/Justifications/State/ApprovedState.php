<?php

namespace App\Justifications\State;

use App\Models\Justification;
use App\Models\JustificationDocument;
use DateTimeInterface;
use LogicException;

class ApprovedState implements JustificationState
{
    public function approve(Justification $justification): void
    {
        // Ya aprobada; no-op
    }

    public function attachEvidence(Justification $justification, JustificationDocument $document): void
    {
        // Tras aprobación no permitimos adjuntar; esto puede ajustarse según reglas.
        throw new LogicException('No se puede adjuntar evidencia a una justificación aprobada.');
    }

    public function comment(Justification $justification, string $comment): void
    {
        // Sin almacenamiento de comentarios; no-op
    }

    public function reject(Justification $justification): void
    {
        // Cambiar de Aprobada a Rechazada (p.ej., tras revisión) si negocio lo permite
        $justification->update(['status' => Justification::STATUS_REJECTED]);
    }

    public function scheduleReevaluation(Justification $justification, DateTimeInterface $when): void
    {
        // Volver a En Proceso para re-evaluación
        $justification->update(['status' => Justification::STATUS_PENDING]);
    }

    public function submit(): void
    {
        // Ya está cerrada/aprobada; no-op
    }
}
