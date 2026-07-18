<?php

namespace App\Filament\Resources\TourPackages;

use App\Filament\Resources\TourPackages\Pages\CreateTourPackage;
use App\Filament\Resources\TourPackages\Pages\EditTourPackage;
use App\Filament\Resources\TourPackages\Pages\ListTourPackages;
use App\Filament\Resources\TourPackages\Schemas\TourPackageForm;
use App\Filament\Resources\TourPackages\Tables\TourPackagesTable;
use App\Filament\Support\TourPackageReadiness;
use App\Models\TourPackage;
use App\Support\TourPackageDuplicator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TourPackageResource extends Resource
{
    protected static ?string $model = TourPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'Travel Products';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        $count = TourPackage::query()
            ->where('is_active', true)
            ->where(fn ($query) => TourPackageReadiness::applyIncomplete($query))->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return TourPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TourPackagesTable::configure($table);
    }

    public static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label('Duplicate')
            ->icon('heroicon-o-square-2-stack')
            ->color('gray')
            ->action(function (TourPackage $record, Action $action, TourPackageDuplicator $duplicator): void {
                $copy = $duplicator->duplicate($record);

                Notification::make()
                    ->title('Package duplicated')
                    ->body('The copy was created as an unpublished draft. Review it before publishing.')
                    ->success()
                    ->send();

                $action->redirect(static::getUrl('edit', ['record' => $copy]));
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTourPackages::route('/'),
            'create' => CreateTourPackage::route('/create'),
            'edit' => EditTourPackage::route('/{record}/edit'),
        ];
    }
}
