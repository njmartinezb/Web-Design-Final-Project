<?php

namespace App\Justifications\Commands;

use App\Models\Justification;

class ApproveJustification implements JustificationCommand
{
    public function __construct(
        protected int $id,
        protected ?string $previousStatus = null,
    ) {}

    public function commandId(): string
    {
        return 'approve';
    }

    public function justificationId(): int
    {
        return $this->id;
    }

    public function execute(Justification $justification): void
    {
        // keep previous status to support undo
        $this->previousStatus ??= $justification->status;
        $justification->approve();
    }

    public function undo(Justification $justification): void
    {
        // revert to known previous status or default to pending
        $justification->update([
            'status' => $this->previousStatus ?? Justification::STATUS_PENDING,
        ]);
    }
}
