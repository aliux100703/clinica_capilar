<?php

namespace App\Filament\Resources;

use App\Models\ValuationRequest;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ValuationRequestResource extends Resource
{
    protected static ?string $model = ValuationRequest::class;

    protected static ?string $modelLabel = 'Solicitudes de valoración';

    protected static ?string $pluralModelLabel = 'Solicitudes de valoración';

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
            TextInput::make('treatment_id')->disabled()->dehydrated(false),
            TextInput::make('preferred_date')->disabled()->dehydrated(false),
            TextInput::make('preferred_time')->disabled()->dehydrated(false),
            TextInput::make('consent')->disabled()->dehydrated(false),
            TextInput::make('consented_at')->disabled()->dehydrated(false),
            Select::make('status')->options(['pending' => 'pending', 'in_review' => 'in_review', 'contacted' => 'contacted', 'closed' => 'closed'])->required()->default('pending'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('status')->sortable(),
            TextColumn::make('preferred_date')->sortable(),
            TextColumn::make('created_at')->sortable(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageValuationRequests::route('/')];
    }
}
