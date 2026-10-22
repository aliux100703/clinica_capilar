<?php

namespace App\Filament\Resources;

use App\Models\Treatment;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TreatmentResource extends Resource
{
    protected static ?string $model = Treatment::class;

    protected static ?string $modelLabel = 'Tratamientos';

    protected static ?string $pluralModelLabel = 'Tratamientos';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(150),
            TextInput::make('slug')->required()->maxLength(180)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true),
            Textarea::make('description')->required()->maxLength(5000),
            Textarea::make('content')->maxLength(50000),
            TextInput::make('image')->url()->maxLength(2048),
            Select::make('status')->options(['draft' => 'draft', 'published' => 'published', 'archived' => 'archived'])->required()->default('draft'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->sortable(),
            TextColumn::make('slug')->sortable(),
            TextColumn::make('status')->sortable(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTreatments::route('/')];
    }
}
