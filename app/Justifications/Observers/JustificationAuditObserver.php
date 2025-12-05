<?php

namespace App\Justifications\Observers;

use App\Justifications\Observers\JustificationCreatedEvent;
use App\Justifications\Observers\JustificationEvent;
use App\Justifications\Observers\JustificationStatusChangedEvent;
use Illuminate\Support\Facades\Log;

class JustificationAuditObserver implements JustificationObserver
{
    public function handle(JustificationCreatedEvent|JustificationStatusChangedEvent $event): void
    {
        $this->update($event);
    }

    public function update(JustificationEvent $event): void
    {
        $justification = $event->justification();

        Log::info('Justification event observed', [
            'justification_id' => $justification->id,
            'status' => $justification->status,
            'event' => $event::class,
        ]);
    }
}
