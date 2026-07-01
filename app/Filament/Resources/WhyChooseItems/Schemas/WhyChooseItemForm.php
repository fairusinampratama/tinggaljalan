<?php

namespace App\Filament\Resources\WhyChooseItems\Schemas;

use App\Models\WhyChooseItem;
use Closure;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WhyChooseItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                ->schema([
                    Group::make([
                        Section::make('Primary Content')
                            ->schema([
                                TextInput::make('title.us')
                                    ->label('Title (English)')
                                    ->required(),
                                Textarea::make('text.us')
                                    ->label('Description (English)')
                                    ->required()
                                    ->rows(3),
                            ]),

                        Section::make('Translations')
                            ->schema([
                                TextInput::make('title.id')->label('Title (Indonesian)'),
                                TextInput::make('title.cn')->label('Title (Chinese)'),
                                Textarea::make('text.id')->label('Description (Indonesian)')->rows(3),
                                Textarea::make('text.cn')->label('Description (Chinese)')->rows(3),
                            ])
                            ->columns(2)
                            ->collapsed()
                            ->collapsible(),
                    ])->columnSpan(['lg' => 2]),

                    Group::make([
                        Section::make('Settings')
                            ->schema([
                                Select::make('icon')
                                    ->label('Icon')
                                    ->options([
                                        'compass' => 'Compass',
                                        'shield' => 'Shield',
                                        'heart' => 'Heart',
                                        'star' => 'Star',
                                        'map' => 'Map',
                                        'map-pin' => 'Map Pin',
                                        'sun' => 'Sun',
                                        'umbrella' => 'Umbrella',
                                        'users' => 'Users',
                                        'thumbs-up' => 'Thumbs Up',
                                        'camera' => 'Camera',
                                        'coffee' => 'Coffee',
                                    ])
                                    ->searchable()
                                    ->required(),
                                TextInput::make('sort_order')->required()
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                                Toggle::make('is_active')->required()
                                    ->label('Is Active')
                                    ->default(true)
                                    ->rules([
                                        function (?WhyChooseItem $record) {
                                            return function (string $attribute, $value, Closure $fail) use ($record) {
                                                if ($value) {
                                                    $count = WhyChooseItem::where('is_active', true)
                                                        ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                                        ->count();
                                                    if ($count >= 3) {
                                                        $fail('You can only have at most 3 active Why Choose Us items.');
                                                    }
                                                }
                                            };
                                        },
                                    ]),
                            ]),
                    ])->columnSpan(['lg' => 1]),
                ])
                ->columnSpanFull()
            ]);
    }
}
