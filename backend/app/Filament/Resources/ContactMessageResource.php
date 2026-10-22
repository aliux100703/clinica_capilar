<?php

namespace App\Filament\Resources;

use App\Models\ContactMessage;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $modelLabel = 'Mensajes';

    protected static ?string $pluralModelLabel = 'Mensajes';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->disabled()->dehydrated(false),
            TextInput::make('email')->disabled()->dehydrated(false),
            TextInput::make('phone')->disabled()->dehydrated(false),
            TextInput::make('consent')->disabled()->dehydrated(false),
            TextInput::make('consented_at')->disabled()->dehydrated(false),
            Textarea::make('message')->disabled()->dehydrated(false),
            Select::make('status')->options(['pending' => 'pending', 'in_review' => 'in_review', 'closed' => 'closed'])->required()->default('pending'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('status')->sortable(),
            TextColumn::make('created_at')->sortable(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContactMessages::route('/')];
    }
}
