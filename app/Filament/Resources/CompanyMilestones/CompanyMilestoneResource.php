<?php

namespace App\Filament\Resources\CompanyMilestones;

use App\Filament\Support\AdminForm;
use App\Models\CompanyMilestone;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyMilestoneResource extends Resource
{
    protected static ?string $model = CompanyMilestone::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'About Us';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Section::make('Milestone')->schema([
                    TextInput::make('period.us')->label('Period / year (English)')->required(),
                    TextInput::make('title.us')->label('Title (English)')->required(),
                    Textarea::make('description.us')->label('Description (English)')->required()->rows(4),
                    Section::make('Translations')->schema([
                        TextInput::make('period.id')->label('Period (Indonesian)'),
                        TextInput::make('period.cn')->label('Period (Chinese)'),
                        TextInput::make('title.id')->label('Title (Indonesian)'),
                        TextInput::make('title.cn')->label('Title (Chinese)'),
                        Textarea::make('description.id')->label('Description (Indonesian)')->rows(4),
                        Textarea::make('description.cn')->label('Description (Chinese)')->rows(4),
                    ])->columns(2)->collapsed()->collapsible(),
                    AdminForm::imageUpload('image', 'Optional milestone image', 'admin/about/milestones'),
                    TextInput::make('image_alt.us')->label('Image alt text (English)'),
                    TextInput::make('image_alt.id')->label('Image alt text (Indonesian)'),
                    TextInput::make('image_alt.cn')->label('Image alt text (Chinese)'),
                ])->columnSpan(['lg' => 2]),
                Section::make('Settings')->schema([
                    TextInput::make('sort_order')->numeric()->default(0)->required(),
                    Toggle::make('is_sample')->label('Show sample marker')->helperText('Keep enabled until this milestone is confirmed.'),
                    Toggle::make('is_active')->label('Published')->default(true),
                ])->columnSpan(['lg' => 1]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort_order')->defaultSort('sort_order')->columns([
            TextColumn::make('period.us')->label('Period'),
            TextColumn::make('title.us')->label('Title')->searchable(),
            IconColumn::make('is_sample')->label('Sample')->boolean(),
            IconColumn::make('is_active')->label('Published')->boolean(),
        ])->actions([EditAction::make(), DeleteAction::make()])->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyMilestones::route('/'),
            'create' => Pages\CreateCompanyMilestone::route('/create'),
            'edit' => Pages\EditCompanyMilestone::route('/{record}/edit'),
        ];
    }
}
