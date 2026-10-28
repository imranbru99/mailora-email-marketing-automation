<?php

namespace App\Filament\Resources;

use App\Models\Article;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends TenantResource
{
    protected static ?string $model = Article::class;

    public static string $module = 'knowledge-base';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('slug')->required()->maxLength(200)->helperText('A unique public URL name within this business.'), TextInput::make('category')->maxLength(255), Textarea::make('body')->rows(8)->columnSpanFull()->required(), Toggle::make('published')])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('slug')->sortable()->toggleable(), Tables\Columns\TextColumn::make('category')->sortable()->toggleable(), Tables\Columns\TextColumn::make('published')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make(), Tables\Actions\Action::make('preview')->url(fn ($record) => route('article.show', $record->public_id))->openUrlInNewTab()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => ArticleResource\Pages\ManageRecords::route('/')];
    }
}
