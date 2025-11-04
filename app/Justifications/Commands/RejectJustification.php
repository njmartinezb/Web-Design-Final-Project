<?php

namespace App\Justifications\Commands;

use App\Models\Justification;

class RejectJustification implements JustificationCommand
{
    public function __construct(
        protected int $id,
        protected ?string $previousStatus = null,
    ) {}

    public function commandId(): string
    {
        return 'reject';
    }

    public function justificationId(): int
    {
        return $this->id;
    }

    public function execute(Justification $justification): void
    {
        $this->previousStatus ??= $justification->status;
        $justification->reject();
    }

    public function undo(Justification $justification): void
    {
        $justification->update([
            'status' => $this->previousStatus ?? Justification::STATUS_PENDING,
        ]);
    }
}
