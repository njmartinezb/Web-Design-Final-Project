<?php

namespace App\Justifications\Commands;

use App\Models\Justification;

/**
 * Command interface for Justification actions.
 *
 * Methods based on the provided UML:
 * - commandId(): string
 * - execute(Justification): void
 * - justificationId(): int
 * - undo(Justification): void
 */
interface JustificationCommand
{
    /** Identifier of the command, e.g. "approve" or "reject" */
    public function commandId(): string;

    /** The target Justification id */
    public function justificationId(): int;

    /** Execute the command against the provided model */
    public function execute(Justification $justification): void;

    /** Revert the command if possible */
    public function undo(Justification $justification): void;
}
