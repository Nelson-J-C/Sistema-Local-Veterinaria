<?php

namespace App\Filament\Resources\Consultations;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Consultations\Pages\CreateConsultation;
use App\Filament\Resources\Consultations\Pages\EditConsultation;
use App\Filament\Resources\Consultations\Pages\ListConsultations;
use App\Filament\Resources\Consultations\Pages\ViewConsultation;
use App\Filament\Resources\Consultations\RelationManagers\AddendaRelationManager;
use App\Filament\Resources\Consultations\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Consultations\RelationManagers\DiagnosesRelationManager;
use App\Filament\Resources\Consultations\RelationManagers\MeasurementsRelationManager;
use App\Filament\Resources\Consultations\RelationManagers\PrescriptionsRelationManager;
use App\Filament\Resources\Consultations\Schemas\ConsultationForm;
use App\Filament\Resources\Consultations\Schemas\ConsultationInfolist;
use App\Filament\Resources\Consultations\Tables\ConsultationsTable;
use App\Models\Consultation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ConsultationResource extends Resource
{
    protected static ?string $model = Consultation::class;

    protected static ?string $modelLabel = 'consulta';

    protected static ?string $pluralModelLabel = 'consultas';

    protected static ?string $navigationLabel = 'Consultas';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Clinical;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return ConsultationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ConsultationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsultationsTable::configure($table);
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof Consultation) {
            return null;
        }

        return "Consulta de {$record->patient->name} · {$record->consulted_at->format('d/m/Y H:i')}";
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient.owner', 'vet', 'appointment']);
    }

    public static function getRelations(): array
    {
        return [
            MeasurementsRelationManager::class,
            DiagnosesRelationManager::class,
            PrescriptionsRelationManager::class,
            AddendaRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsultations::route('/'),
            'create' => CreateConsultation::route('/create'),
            'view' => ViewConsultation::route('/{record}'),
            'edit' => EditConsultation::route('/{record}/edit'),
        ];
    }
}
