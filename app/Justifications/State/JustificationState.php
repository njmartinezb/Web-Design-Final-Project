<?php

namespace App\Justifications\State;

use App\Models\Justification;
use App\Models\JustificationDocument;
use DateTimeInterface;

/**
 * Interface basada en el diagrama UML para controlar el ciclo de vida
 * de una Justificación mediante el patrón State.
 */
interface JustificationState
{
    public function approve(Justification $justification): void;

    public function attachEvidence(Justification $justification, JustificationDocument $document): void;

    public function comment(Justification $justification, string $comment): void;

    public function reject(Justification $justification): void;

    public function scheduleReevaluation(Justification $justification, DateTimeInterface $when): void;

    public function submit(): void;
}
