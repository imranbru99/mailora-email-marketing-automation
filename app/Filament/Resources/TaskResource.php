<?php

namespace App\Filament\Resources;

use App\Models\Task;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class TaskResource extends TenantResource
{
    protected static ?string $model = Task::class;

    public static string $module = 'tasks';

    protected static ?string $navigationGroup = 'Sales & Team';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), Textarea::make('description'), Select::make('status')->options(['todo' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'])->default('todo')->required(), Select::make('priority')->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'])->default('normal'), DateTimePicker::make('due_at')])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('status')->searchable()->sortable()->badge(), Tables\Columns\TextColumn::make('priority')->searchable()->sortable()->badge(), Tables\Columns\TextColumn::make('due_at')->searchable()->sortable()->dateTime(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([Tables\Filters\SelectFilter::make('status')->options(fn () => static::getModel()::query()->distinct()->pluck('status', 'status')->all())])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make()])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => TaskResource\Pages\ListRecords::route('/'), 'create' => TaskResource\Pages\CreateRecord::route('/create'), 'edit' => TaskResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
