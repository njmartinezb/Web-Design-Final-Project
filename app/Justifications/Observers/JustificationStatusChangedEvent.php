<?php

namespace App\Justifications\Observers;

use App\Models\Justification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JustificationStatusChangedEvent implements JustificationEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public Justification $justification) {}

    public function justification(): Justification
    {
        return $this->justification;
    }
}
