<?php

namespace App\Justifications\Observers;

use App\Justifications\Observers\JustificationEvent;

interface JustificationObserver
{
    public function update(JustificationEvent $event): void;
}
