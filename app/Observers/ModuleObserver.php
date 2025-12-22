<?php

namespace App\Observers;

use App\Models\Module;

class ModuleObserver
{
    public function saved(Module $module): void
    {
        $module->course?->touch();
    }

    public function deleted(Module $module): void
    {
        $module->course?->touch();
    }

    public function restored(Module $module): void
    {
        $module->course?->touch();
    }
}
