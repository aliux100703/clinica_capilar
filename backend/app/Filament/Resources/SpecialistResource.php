<?php

namespace App\Filament\Resources;

use App\Models\Specialist;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpecialistResource extends Resource
{
    protected static ?string $model = Specialist::class;

    protected static ?string $modelLabel = 'Especialistas';

    protected static ?string $pluralModelLabel = 'Especialistas';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(150),
            TextInput::make('specialty')->required()->maxLength(150),
            Textarea::make('description')->required()->maxLength(5000),
            TextInput::make('photo')->url()->maxLength(2048),
            Repeater::make('credentials')->schema([
                TextInput::make('title')->required()->maxLength(200), TextInput::make('issuer')->required()->maxLength(200), TextInput::make('registration')->required()->maxLength(100),
                TextInput::make('verification_url')->required()->url()->maxLength(2048),
                Toggle::make('verified')->label('Documentación comprobada')->default(false),
            ])->minItems(1)->required()->defaultItems(1),
            Select::make('status')->options(['draft' => 'draft', 'published' => 'published', 'archived' => 'archived'])->required()->default('draft'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->sortable(),
            TextColumn::make('specialty')->sortable(),
            TextColumn::make('status')->sortable(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSpecialists::route('/')];
    }
}
