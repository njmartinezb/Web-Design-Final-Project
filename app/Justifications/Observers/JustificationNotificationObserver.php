<?php

namespace App\Justifications\Observers;

use App\Justifications\Observers\JustificationCreatedEvent;
use App\Justifications\Observers\JustificationEvent;
use App\Justifications\Observers\JustificationStatusChangedEvent;
use Illuminate\Support\Facades\Log;

class JustificationNotificationObserver implements JustificationObserver
{
    // Laravel listener entry-point(s):
    public function handle(JustificationCreatedEvent|JustificationStatusChangedEvent $event): void
    {
        $this->update($event);
    }

    // UML-required method:
    public function update(JustificationEvent $event): void
    {
        $j = $event->justification();

        if ($event instanceof JustificationCreatedEvent) {
            Log::info("NOTIFY: Justificación #{$j->id} creada (status: {$j->status})");
            return;
        }

        if ($event instanceof JustificationStatusChangedEvent) {
            Log::info("NOTIFY: Justification #{$j->id}  cambiada a '{$j->status}'");
            return;
        }
    }
}
