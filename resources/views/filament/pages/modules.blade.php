<x-filament-panels::page><div class="mailora-modules">
<div class="module-presets"><select wire:model="preset" aria-label="Business preset">@foreach(config('platform.presets') as $preset)<option>{{ $preset }}</option>@endforeach</select><x-filament::button wire:click="applyPreset" wire:confirm="Replace module selections with this preset? Existing data will be preserved.">Apply preset</x-filament::button></div>
<div class="module-banner"><strong>Your workspace, your way.</strong><p>Enable the capabilities your business needs. Disabling a module preserves its data. Dependencies are enabled automatically.</p><small>The catalog covers the full product brief. Entries may be partially implemented or planned; see the delivery matrix in the project documentation.</small></div>
<x-filament::input.wrapper><x-filament::input type="search" wire:model.live.debounce.300ms="search" placeholder="Search 108 modules…" /></x-filament::input.wrapper>
@foreach($modules as $category=>$items)
<h2 class="module-heading">{{ $category }}</h2><div class="module-grid">
@foreach($items as $module)
@php($enabled=app(\App\Services\ModuleRegistry::class)->enabled($module->key))
<div class="module-card"><div class="module-card-header"><h3>{{ $module->name }}</h3><button type="button" wire:click="toggle('{{ $module->key }}')" wire:loading.attr="disabled" @disabled($module->is_core) role="switch" aria-checked="{{ $enabled?'true':'false' }}" aria-label="Toggle {{ $module->name }}" class="module-switch">{{ $module->is_core?'Core':($enabled?'On':'Off') }}</button></div><p>{{ Str::limit($module->description,170) }}</p>@if($module->depends_on)<small>Requires: {{ implode(', ',$module->depends_on) }}</small>@endif</div>
@endforeach</div>
@endforeach
</div></x-filament-panels::page>
