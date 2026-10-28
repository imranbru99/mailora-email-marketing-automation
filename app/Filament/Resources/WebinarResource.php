<?php

namespace App\Filament\Resources;

use App\Models\Webinar;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class WebinarResource extends TenantResource
{
    protected static ?string $model = Webinar::class;

    public static string $module = 'webinars';

    protected static ?string $navigationGroup = 'Growth';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), Textarea::make('description')->rows(8)->columnSpanFull(), DateTimePicker::make('starts_at')->required(), DateTimePicker::make('ends_at')->required(), TextInput::make('location')->maxLength(255), TextInput::make('capacity')->numeric()->minValue(0)->default(0), Toggle::make('published')])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('starts_at')->sortable()->dateTime()->toggleable(), Tables\Columns\TextColumn::make('ends_at')->sortable()->dateTime()->toggleable(), Tables\Columns\TextColumn::make('location')->sortable()->toggleable(), Tables\Columns\TextColumn::make('capacity')->sortable()->toggleable(), Tables\Columns\TextColumn::make('published')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make(), Tables\Actions\Action::make('preview')->url(fn ($record) => route('webinar.show', $record->public_id))->openUrlInNewTab()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => WebinarResource\Pages\ManageRecords::route('/')];
    }
}
