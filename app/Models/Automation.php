<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property list<array{type:string,value?:string,identity_id?:int,field?:string,on_true?:int,on_false?:int,email?:array{subject:string,html:string}}> $steps
 */
class Automation extends TenantModel
{
    use SoftDeletes;

    protected static function booted(): void
    {
        parent::booted();
        static::saving(function (self $automation) {
            if ($automation->status !== 'live' || ! $automation->isDirty('status')) {
                return;
            }
            $steps = $automation->steps;
            foreach ($steps as &$step) {
                if ($step['type'] === 'email') {
                    $template = Template::findOrFail((int) ($step['value'] ?? 0));
                    $step['email'] = ['subject' => $template->subject, 'html' => $template->html];
                }
            }
            unset($step);
            $automation->steps = $steps;
        });
    }

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['steps' => 'array'];
    }
}
