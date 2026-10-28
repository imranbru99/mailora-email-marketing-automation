<?php

namespace App\Filament\Resources;

use App\Models\Ticket;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends TenantResource
{
    protected static ?string $model = Ticket::class;

    public static string $module = 'tickets';

    protected static ?string $navigationGroup = 'Sales & Team';

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('requester_email')->email(), Textarea::make('description')->rows(8)->columnSpanFull(), Select::make('status')->options(['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'])->required()->default('open'), Select::make('priority')->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'])->default('normal')->required(), Textarea::make('response')->rows(8)->columnSpanFull()])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('requester_email')->sortable()->toggleable(), Tables\Columns\TextColumn::make('status')->sortable()->badge()->toggleable(), Tables\Columns\TextColumn::make('priority')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => TicketResource\Pages\ManageRecords::route('/')];
    }
}
