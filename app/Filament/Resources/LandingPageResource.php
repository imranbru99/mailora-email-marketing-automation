<?php

namespace App\Filament\Resources;

use App\Models\LandingPage;
use App\Models\SignupForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class LandingPageResource extends TenantResource
{
    protected static ?string $model = LandingPage::class;

    public static string $module = 'landing-pages';

    protected static ?string $navigationGroup = 'Growth';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('slug')->required()->maxLength(200)->helperText('A unique public URL name within this business.'), TextInput::make('headline')->maxLength(255), Textarea::make('body')->rows(8)->columnSpanFull()->required(), TextInput::make('button_label')->maxLength(255), Select::make('signup_form_id')->options(fn () => SignupForm::pluck('name', 'id'))->label('Signup form'), Toggle::make('published'), Textarea::make('seo_description')->rows(8)->columnSpanFull()])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('slug')->sortable()->toggleable(), Tables\Columns\TextColumn::make('headline')->sortable()->toggleable(), Tables\Columns\TextColumn::make('button_label')->sortable()->toggleable(), Tables\Columns\TextColumn::make('published')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make(), Tables\Actions\Action::make('preview')->url(fn ($record) => route('landing.show', $record->public_id))->openUrlInNewTab()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => LandingPageResource\Pages\ManageRecords::route('/')];
    }
}
