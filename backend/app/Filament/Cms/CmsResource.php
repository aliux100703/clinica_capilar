<?php

namespace App\Filament\Cms;

use App\Support\CmsDefinition;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

abstract class CmsResource extends Resource
{
    protected static bool $isGloballySearchable = false;

    public static function getModelLabel(): string
    {
        return CmsDefinition::for(static::getModel())['label'];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin' && (! (CmsDefinition::for(static::getModel())['singleton'] ?? false) || ! static::getModel()::exists());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(CmsFields::build(CmsDefinition::for(static::getModel())['fields'], static::getModel()));
    }

    public static function table(Table $table): Table
    {
        $definition = CmsDefinition::for(static::getModel());
        $first = $definition['fields'][0]['name'];
        $actions = [EditAction::make()->label('Editar')->successNotification(function ($record): Notification {
            if ($record->status !== 'published') {
                return Notification::make()->success()->title('Borrador guardado')->body('Los cambios todavía no aparecen en la web. Para mostrarlos, selecciona Publicado en Estado y guarda.');
            }

            return Notification::make()->success()->title('Contenido publicado')->body('Los cambios están disponibles para la web. Recarga la página pública para verlos.');
        })];
        if (in_array(class_basename(static::getModel()), ['Result', 'Testimonial'])) {
            $type = class_basename(static::getModel()) === 'Result' ? 'results' : 'testimonials';
            $actions[] = Action::make('consent')->label('Documento privado')->url(fn ($record) => url('/admin/consents/'.$type.'/'.$record->getKey()))->openUrlInNewTab()->visible(fn ($record) => filled($record->consent_document));
        }

        return $table->columns([TextColumn::make($first)->label($definition['fields'][0]['label'])->limit(60), TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn (string $state): string => $state === 'published' ? 'Publicado' : 'Borrador')->color(fn (string $state): string => $state === 'published' ? 'success' : 'warning'), TextColumn::make('updated_at')->label('Actualizado')->dateTime()])->recordActions($actions)->defaultSort('id');
    }
}
