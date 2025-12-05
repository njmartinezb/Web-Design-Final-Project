<?php

namespace App\Justifications\Observers;

interface JustificationObservable
{
    public function attachJustificationObserver(JustificationObserver $observer): void;

    public function detachJustificationObserver(JustificationObserver $observer): void;

    public function notifyJustificationObserver(): void;
}
