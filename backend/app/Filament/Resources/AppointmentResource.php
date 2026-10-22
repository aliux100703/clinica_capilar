<?php

namespace App\Filament\Resources;

use App\Models\Appointment;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $modelLabel = 'Citas';

    protected static ?string $pluralModelLabel = 'Citas';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('valuation_request_id')->relationship('valuationRequest', 'id')->searchable()->required(),
            DatePicker::make('date')->required(),
            TimePicker::make('time')->seconds(false)->required(),
            Select::make('status')->options(['pending' => 'pending', 'confirmed' => 'confirmed', 'completed' => 'completed', 'cancelled' => 'cancelled'])->required()->default('pending'),
            Textarea::make('internal_notes')->maxLength(2000)->helperText('Solo información operativa; no incluir datos médicos.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('valuation_request_id')->sortable(),
            TextColumn::make('date')->sortable(),
            TextColumn::make('time')->sortable(),
            TextColumn::make('status')->sortable(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAppointments::route('/')];
    }
}
