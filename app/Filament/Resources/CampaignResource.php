<?php

namespace App\Filament\Resources;

use App\Models\AudienceList;
use App\Models\Campaign;
use App\Models\ReviewComment;
use App\Models\Segment;
use App\Models\SendingIdentity;
use App\Models\Template;
use App\Services\Audit;
use App\Services\CampaignPreflight;
use App\Services\CampaignReview;
use App\Services\CampaignService;
use App\Services\ModuleRegistry;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CampaignResource extends TenantResource
{
    protected static ?string $model = Campaign::class;

    public static string $module = 'campaigns';

    protected static ?string $navigationGroup = 'Campaigns';

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Wizard\Step::make('Campaign')->schema([TextInput::make('name')->required(), TextInput::make('subject')->required()->maxLength(200), TextInput::make('preheader')->maxLength(250)]),
                Wizard\Step::make('Audience & sender')->schema([
                    Select::make('sending_identity_id')->label('Sending identity')->options(fn () => SendingIdentity::pluck('email', 'id'))->required(),
                    Select::make('audience_list_id')->label('List')->options(fn () => AudienceList::pluck('name', 'id'))->helperText('Leave empty to include all subscribed contacts.'),
                    Select::make('segment_id')->label('Segment')->options(fn () => Segment::pluck('name', 'id')),
                ]),
                Wizard\Step::make('Content')->schema([
                    Select::make('template_id')->label('Start with a template')->options(fn () => Template::pluck('name', 'id'))->live()->dehydrated(false)->afterStateUpdated(function ($state, Set $set) {
                        if ($t = Template::find($state)) {
                            $set('html', $t->html);
                            $set('subject', $t->subject);
                        }
                    }),
                    Textarea::make('html')->required()->rows(18)->helperText('Include {{unsubscribe_url}}. Contact fields are escaped safely.'),
                ]),
                Wizard\Step::make('Schedule & review')->schema([Toggle::make('tracking')->default(true), DateTimePicker::make('scheduled_at')->label('Schedule (UTC)')->helperText('Leave empty to send immediately after approval.'), Placeholder::make('review')->content('Save your draft, approve it, then choose Queue send. The compliance guard runs again for every recipient.')]),
            ])->skippable()->columnSpanFull(), ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('subject')->searchable()->sortable(), Tables\Columns\TextColumn::make('status')->searchable()->sortable()->badge(), Tables\Columns\TextColumn::make('sent_count')->searchable()->sortable(), Tables\Columns\TextColumn::make('open_count')->searchable()->sortable(), Tables\Columns\TextColumn::make('scheduled_at')->searchable()->sortable()->dateTime(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([Tables\Filters\SelectFilter::make('status')->options(fn () => static::getModel()::query()->distinct()->pluck('status', 'status')->all())])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('preflight')->label('Pre-send checklist')->visible(fn () => app(ModuleRegistry::class)->enabled('checklist'))->modalContent(fn ($record) => view('filament.campaign-preflight', ['report' => app(CampaignPreflight::class)->inspect($record)]))->modalSubmitAction(false)->modalCancelActionLabel('Close'),
            Tables\Actions\Action::make('comments')->label('Review comments')->visible(fn () => app(ModuleRegistry::class)->enabled('comments'))->modalContent(fn ($record) => view('filament.campaign-comments', ['comments' => ReviewComment::where('campaign_id', $record->id)->latest()->limit(100)->get()]))->form([Textarea::make('body')->label('New comment')->maxLength(5000)->required()])->action(fn ($record, array $data) => app(CampaignReview::class)->comment($record, $data['body']))->modalSubmitAction(fn ($action) => auth()->user()->allows('comments', 'create') ? $action->label('Post comment') : false),
            Tables\Actions\Action::make('requestReview')->requiresConfirmation()->visible(fn ($record) => $record->status === 'draft' && auth()->user()->allows('campaigns', 'edit') && app(ModuleRegistry::class)->enabled('approvals'))->action(fn ($record) => app(CampaignReview::class)->request($record)),
            Tables\Actions\Action::make('reject')->label('Request changes')->visible(fn ($record) => $record->status === 'pending approval' && auth()->user()->allows('campaigns', 'approve') && app(ModuleRegistry::class)->enabled('approvals'))->form([Textarea::make('reason')->required()->maxLength(5000)])->action(fn ($record, array $data) => app(CampaignReview::class)->reject($record, $data['reason'])),
            Tables\Actions\Action::make('cancel')->color('danger')->requiresConfirmation()->modalDescription('Queued messages will stop. Messages already accepted by SMTP cannot be recalled.')->visible(fn ($record) => ! in_array($record->status, ['sent', 'cancelled']) && auth()->user()->allows('campaigns', 'send'))->action(fn ($record) => app(CampaignReview::class)->cancel($record)),
            Tables\Actions\Action::make('approve')->icon('heroicon-o-check-badge')->requiresConfirmation()->visible(fn ($record) => in_array($record->status, ['draft', 'pending approval']) && ! $record->approved_at && auth()->user()->allows('campaigns', 'approve'))->action(fn ($record) => app(CampaignService::class)->approve($record)), Tables\Actions\Action::make('send')->label('Queue send')->icon('heroicon-o-paper-airplane')->requiresConfirmation()->visible(fn ($record) => in_array($record->status, ['draft', 'paused']) && auth()->user()->allows('campaigns', 'send'))->action(fn ($record) => app(CampaignService::class)->launch($record)), Tables\Actions\Action::make('pause')->requiresConfirmation()->visible(fn ($record) => in_array($record->status, ['preparing', 'sending', 'scheduled']) && auth()->user()->allows('campaigns', 'send'))->action(function ($record) {
                $record->update(['status' => 'paused']);
                Audit::record('campaign.paused', $record);
            }), Tables\Actions\Action::make('duplicate')->visible(fn () => static::canCreate())->action(function ($record) {
                $copy = $record->replicate(['public_id', 'approved_at', 'approved_by', 'sent_at']);
                $copy->name .= ' (copy)';
                $copy->status = 'draft';
                $copy->sent_count = 0;
                $copy->open_count = 0;
                $copy->click_count = 0;
                $copy->bounce_count = 0;
                $copy->scheduled_at = null;
                $copy->save();
            })])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => CampaignResource\Pages\ListRecords::route('/'), 'create' => CampaignResource\Pages\CreateRecord::route('/create'), 'edit' => CampaignResource\Pages\EditRecord::route('/{record}/edit')];
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Campaign && parent::canEdit($record) && in_array($record->status, ['draft', 'pending approval'], true);
    }
}
