<?php

namespace App\Filament\Pages;

use App\Jobs\CheckEmail;
use App\Models\Contact;
use App\Models\EmailCheck;
use App\Services\ModuleRegistry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ListHygiene extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Audience';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.table';

    protected ?string $subheading = 'Check syntax, mail routing, common typos, role addresses, and a configurable disposable-domain list. These checks never grant consent or prove a mailbox exists.';

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('verification') && (auth()->user()?->allows('contacts') ?? false);
    }

    public function table(Table $table): Table
    {
        return $table->query(Contact::query()->leftJoin('email_checks', 'email_checks.contact_id', '=', 'contacts.id')->select('contacts.*', 'email_checks.status as verification_status', 'email_checks.updated_at as checked_at'))->columns([Tables\Columns\TextColumn::make('email')->searchable(), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('verification_status')->badge()->placeholder('Not checked'), Tables\Columns\TextColumn::make('checked_at')->dateTime()])->actions([Tables\Actions\Action::make('verify')->label('Queue check')->visible(fn () => auth()->user()->allows('contacts', 'edit'))->action(function (Contact $record) {
            CheckEmail::dispatch($record->business_id, $record->branch_id, $record->id);
            Notification::make()->title('Email check queued')->success()->send();
        }), Tables\Actions\Action::make('findings')->modalContent(fn (Contact $record) => view('filament.email-findings', ['check' => EmailCheck::where('contact_id', $record->id)->first()]))->modalSubmitAction(false)->modalCancelActionLabel('Close')])->bulkActions([Tables\Actions\BulkAction::make('verify')->label('Check selected emails')->visible(fn () => auth()->user()->allows('contacts', 'edit'))->requiresConfirmation()->action(function (Collection $records) {
            abort_unless(auth()->user()->allows('contacts', 'edit') && static::canAccess(), 403);
            foreach ($records as $record) {
                if ($record instanceof Contact) {
                    CheckEmail::dispatch($record->business_id, $record->branch_id, $record->id);
                }
            }
        })->deselectRecordsAfterCompletion()])->defaultSort('contacts.id','desc')->poll('15s');
    }
}
