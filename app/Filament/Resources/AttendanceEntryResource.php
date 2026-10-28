<?php

namespace App\Filament\Resources;

use App\Models\AttendanceEntry;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceEntryResource extends TenantResource
{
    protected static ?string $model = AttendanceEntry::class;

    public static string $module = 'attendance';

    protected static ?string $navigationGroup = 'Sales & Team';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('employee_code')->maxLength(255)->required(), DateTimePicker::make('punched_at')->required(), Select::make('direction')->options(['in' => 'In', 'out' => 'Out'])->required(), TextInput::make('source')->maxLength(255)])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('employee_code')->sortable()->toggleable(), Tables\Columns\TextColumn::make('punched_at')->sortable()->dateTime()->toggleable(), Tables\Columns\TextColumn::make('direction')->sortable()->toggleable(), Tables\Columns\TextColumn::make('source')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => AttendanceEntryResource\Pages\ManageRecords::route('/')];
    }
}
