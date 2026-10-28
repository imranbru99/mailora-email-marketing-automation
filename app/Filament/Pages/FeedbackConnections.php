<?php

namespace App\Filament\Pages;

use App\Models\FeedbackEndpoint;
use App\Services\Audit;
use App\Services\ModuleRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class FeedbackConnections extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Delivery';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string $view = 'filament.pages.table';

    protected ?string $subheading = 'Connect an MTA or gateway using the Mailora signed receipt format. Native provider payloads require a translating adapter.';

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('feedback') && (auth()->user()?->allows('settings', 'edit') ?? false);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('create')->label('Add feedback connection')->form([TextInput::make('name')->required()->maxLength(150)])->action(function (array $data) {
            abort_unless(static::canAccess(), 403);
            FeedbackEndpoint::create($data);
        })];
    }

    public function table(Table $table): Table
    {
        return $table->query(FeedbackEndpoint::query())->columns([Tables\Columns\TextColumn::make('name'), Tables\Columns\IconColumn::make('active')->boolean(), Tables\Columns\TextColumn::make('created_at')->dateTime()])->actions([Tables\Actions\Action::make('credentials')->label('Connection details')->modalContent(fn (FeedbackEndpoint $record) => view('filament.feedback-connection', ['endpoint' => $record]))->modalSubmitAction(false)->modalCancelActionLabel('Close'), Tables\Actions\Action::make('toggle')->label(fn ($record) => $record->active ? 'Disable' : 'Enable')->requiresConfirmation()->action(function (FeedbackEndpoint $record) {
            abort_unless(static::canAccess(), 403);
            $record->update(['active' => ! $record->active]);
        }), Tables\Actions\Action::make('rotate')->label('Rotate secret')->requiresConfirmation()->modalDescription('Your gateway must be updated with the new secret. Existing signatures will stop working.')->action(function (FeedbackEndpoint $record) {
            abort_unless(static::canAccess(), 403);
            $record->update(['secret' => bin2hex(random_bytes(32))]);
            Audit::record('feedback.secret_rotated', $record);
        })]);
    }
}
