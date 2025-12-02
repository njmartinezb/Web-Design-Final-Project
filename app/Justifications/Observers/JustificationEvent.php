<?php

namespace App\Justifications\Observers;

use App\Models\Justification;

interface JustificationEvent
{
    public function justification(): Justification;
}
