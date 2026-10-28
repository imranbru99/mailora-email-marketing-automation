<?php

namespace App\Filament\Resources;

use App\Models\Automation;
use App\Models\SendingIdentity;
use App\Models\Template;
use App\Services\AutomationEngine;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AutomationResource extends TenantResource
{
    protected static ?string $model = Automation::class;

    public static string $module = 'automation';

    protected static ?string $navigationGroup = 'Automation';

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), Select::make('trigger')->options(['contact.created' => 'Contact created', 'form.submitted' => 'Form submitted', 'manual' => 'Manual enrollment'])->required()->default('contact.created'),
            Repeater::make('steps')->schema([Select::make('type')->options(['wait' => 'Wait (minutes)', 'tag' => 'Add tag', 'score' => 'Change score', 'task' => 'Create task', 'unsubscribe' => 'Unsubscribe', 'email' => 'Send email', 'remove_tag' => 'Remove tag', 'set_field' => 'Update field', 'condition' => 'Condition: has tag'])->required()->live(), TextInput::make('value')->required()->hidden(fn (Get $get) => $get('type') === 'email'), Select::make('value')->label('Email template')->options(fn () => Template::pluck('name', 'id'))->required()->searchable()->visible(fn (Get $get) => $get('type') === 'email'), Select::make('identity_id')->label('Sender')->visible(fn (Get $get) => $get('type') === 'email')->required(fn (Get $get) => $get('type') === 'email')->options(fn () => SendingIdentity::pluck('email', 'id')), Select::make('field')->label('Field')->visible(fn (Get $get) => $get('type') === 'set_field')->required(fn (Get $get) => $get('type') === 'set_field')->options(['company' => 'Company', 'locale' => 'Language', 'timezone' => 'Timezone']), TextInput::make('on_true')->numeric()->minValue(0)->label('If true: step index (0 based)')->visible(fn (Get $get) => $get('type') === 'condition'), TextInput::make('on_false')->numeric()->minValue(0)->label('If false: step index (0 based)')->visible(fn (Get $get) => $get('type') === 'condition')])->columns(2)->reorderable()->collapsible()->columnSpanFull()->helperText('Live runs keep a versioned copy of these steps. Pause before editing.'), ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('trigger')->searchable()->sortable(), Tables\Columns\TextColumn::make('status')->searchable()->sortable()->badge(), Tables\Columns\TextColumn::make('version')->searchable()->sortable(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([Tables\Filters\SelectFilter::make('status')->options(fn () => static::getModel()::query()->distinct()->pluck('status', 'status')->all())])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\Action::make('publish')->requiresConfirmation()->visible(fn ($record) => $record->status !== 'live' && static::canCreate())->action(function ($record) {
            app(AutomationEngine::class)->validate($record->steps);
            foreach ($record->steps as $step) {
                if ($step['type'] === 'email') {
                    abort_unless(auth()->user()->allows('campaigns', 'send'), 403);
                }
            }
            $record->update(['status' => 'live', 'version' => $record->version + 1]);
        }), Tables\Actions\Action::make('pause')->requiresConfirmation()->visible(fn ($record) => $record->status === 'live' && static::canCreate())->action(fn ($record) => $record->update(['status' => 'paused']))])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => AutomationResource\Pages\ListRecords::route('/'), 'create' => AutomationResource\Pages\CreateRecord::route('/create'), 'edit' => AutomationResource\Pages\EditRecord::route('/{record}/edit')];
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Automation && parent::canEdit($record) && $record->status !== 'live';
    }
}
