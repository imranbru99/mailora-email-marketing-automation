<?php

namespace App\Services;

use App\Models\Module;

class ModuleCatalog
{
    public function sync(): void
    {
        foreach (config('modules') as $definition) {
            Module::updateOrCreate(['key' => $definition['key']], array_intersect_key($definition, array_flip(['name', 'description', 'category', 'depends_on', 'is_core', 'default_enabled'])));
        }
    }
}
