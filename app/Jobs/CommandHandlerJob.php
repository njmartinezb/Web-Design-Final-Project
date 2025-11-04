<?php

namespace App\Jobs;

use App\Justifications\Commands\JustificationCommand;
use App\Models\Justification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Generic job that loads the Justification by id and executes the provided command.
 *
 * Note: The UML shows handle(Justification): void. In Laravel Jobs the handle method
 * is parameterless; we resolve the model internally to keep queue compatibility.
 */
class CommandHandlerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public JustificationCommand $command)
    {
    }

    public function handle(): void
    {
        $justification = Justification::findOrFail($this->command->justificationId());
        $this->command->execute($justification);
    }
}
