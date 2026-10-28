<?php

namespace App\Filament\Resources;

use App\Models\TimeEntry;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class TimeEntryResource extends TenantResource
{
    protected static ?string $model = TimeEntry::class;

    public static string $module = 'time-tracking';

    protected static ?string $navigationGroup = 'Sales & Team';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('project')->maxLength(255), TextInput::make('minutes')->numeric()->minValue(0)->default(0), DateTimePicker::make('worked_on')->required()])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('project')->sortable()->toggleable(), Tables\Columns\TextColumn::make('minutes')->sortable()->toggleable(), Tables\Columns\TextColumn::make('worked_on')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => TimeEntryResource\Pages\ManageRecords::route('/')];
    }
}
