<?php

namespace App\Filament\Resources;

use App\Models\Template;
use App\Services\TemplateRenderer;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class TemplateResource extends TenantResource
{
    protected static ?string $model = Template::class;

    public static string $module = 'templates';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), Select::make('category')->options(array_combine(['Welcome', 'Newsletter', 'Promotion', 'Commerce', 'Event', 'Retention'], ['Welcome', 'Newsletter', 'Promotion', 'Commerce', 'Event', 'Retention']))->required(), TextInput::make('subject')->required()->columnSpanFull(),
            Tabs::make('Editor')->tabs([
                Tabs\Tab::make('HTML')->schema([Textarea::make('html')->required()->rows(18)->helperText('Personalize with {{first_name|friend}}. Always include {{unsubscribe_url}}.')]),
                Tabs\Tab::make('Block builder')->schema([Builder::make('blocks')->blocks([
                    Builder\Block::make('heading')->schema([TextInput::make('text')->required()]),
                    Builder\Block::make('text')->schema([RichEditor::make('text')->required()->disableToolbarButtons(['attachFiles'])]),
                    Builder\Block::make('button')->schema([TextInput::make('label')->required(), TextInput::make('url')->url()->required()]),
                    Builder\Block::make('image')->schema([TextInput::make('url')->url()->required(), TextInput::make('alt')->required()]),
                    Builder\Block::make('divider')->schema([]),
                ])->collapsible()->reorderable()->helperText('Use Render blocks after saving to replace the HTML with this layout.')]),
            ])->columnSpanFull(), ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('category')->searchable()->sortable()->badge(), Tables\Columns\TextColumn::make('subject')->searchable()->sortable(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\Action::make('preview')->url(fn ($record) => route('template.preview', $record->public_id))->openUrlInNewTab(), Tables\Actions\Action::make('render')->label('Render blocks')->requiresConfirmation()->visible(fn () => static::canCreate())->action(fn ($record) => $record->update(['html' => app(TemplateRenderer::class)->blocks($record->blocks ?? [])]))])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => TemplateResource\Pages\ListRecords::route('/'), 'create' => TemplateResource\Pages\CreateRecord::route('/create'), 'edit' => TemplateResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
