<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * @property list<array{type:string,value?:string,identity_id?:int,field?:string,on_true?:int,on_false?:int,email?:array{subject:string,html:string}}> $steps
 * @property list<array<string,mixed>>|null $history
 * @property Carbon|null $next_run_at
 */
class AutomationRun extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['steps' => 'array', 'history' => 'array', 'next_run_at' => 'datetime'];
    }
}
