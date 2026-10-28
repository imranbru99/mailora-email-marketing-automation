<?php

namespace App\Filament\Resources;

use App\Services\ModuleRegistry;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class TenantResource extends Resource
{
    public static string $module = '';

    public static function canViewAny(): bool
    {
        return app(ModuleRegistry::class)->enabled(static::$module) && (auth()->user()?->allows(static::$module) ?? false);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny() && auth()->user()->allows(static::$module, 'create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny() && auth()->user()->allows(static::$module, 'edit');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny() && auth()->user()->allows(static::$module, 'delete');
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canRestore(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }
}
