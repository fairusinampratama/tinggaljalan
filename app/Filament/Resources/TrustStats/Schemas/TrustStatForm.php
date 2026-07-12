<?php

namespace App\Filament\Resources\TrustStats\Schemas;

use App\Filament\Support\AdminForm;
use App\Models\TrustStat;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TrustStatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Display settings')
                    ->description('The homepage displays at most four active trust stats.')
                    ->schema([
                        ToggleButtons::make('icon_key')
                            ->label('Icon')
                            ->options([
                                'compass' => 'Compass',
                                'map-pin' => 'Location',
                                'shield-check' => 'Trust',
                                'star' => 'Rating',
                            ])
                            ->icons([
                                'compass' => Heroicon::OutlinedMap,
                                'map-pin' => Heroicon::OutlinedMapPin,
                                'shield-check' => Heroicon::OutlinedShieldCheck,
                                'star' => Heroicon::OutlinedStar,
                            ])
                            ->default('star')
                            ->required()
                            ->inline()
                            ->helperText('Choose the icon that corresponds to the stat type.')
                            ->columnSpanFull(),
                        TextInput::make('sort_order')->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first.'),
                        Toggle::make('is_active')->required()
                            ->default(fn (): bool => TrustStat::query()->active()->count() < TrustStat::MAX_ACTIVE)
                            ->helperText('Only four trust stats can be active at once.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                AdminForm::localized('title', 'Title', required: true)
                    ->description('The stat title or label (e.g., \'Google Reviews\' or \'Certified Planner\'). Translate to ID, US, and CN.'),
                AdminForm::localized('value', 'Value', required: true)
                    ->description('The numeric or text value of the stat (e.g., \'5.0\' or \'Official Standard\'). Translate to ID, US, and CN.'),
            ]);
    }
}