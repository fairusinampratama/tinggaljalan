<?php

namespace App\Filament\Resources\TeamMembers;

use App\Filament\Support\AdminForm;
use App\Models\TeamMember;
use App\Support\PublicSite;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamMemberResource extends Resource
{
    protected static ?string $model = TeamMember::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'About Us';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Section::make('Public profile')->schema([
                    TextInput::make('name')->required()->maxLength(120),
                    TextInput::make('role.us')->label('Role (English)')->required(),
                    Textarea::make('biography.us')->label('Biography (English)')->required()->rows(4),
                    Section::make('Translations')->schema([
                        TextInput::make('role.id')->label('Role (Indonesian)'),
                        TextInput::make('role.cn')->label('Role (Chinese)'),
                        Textarea::make('biography.id')->label('Biography (Indonesian)')->rows(4),
                        Textarea::make('biography.cn')->label('Biography (Chinese)')->rows(4),
                    ])->columns(2)->collapsed()->collapsible(),
                    AdminForm::imageUpload('portrait', 'Portrait', 'admin/about/team'),
                    TextInput::make('portrait_alt.us')->label('Portrait alt text (English)')->required(),
                    TextInput::make('portrait_alt.id')->label('Portrait alt text (Indonesian)'),
                    TextInput::make('portrait_alt.cn')->label('Portrait alt text (Chinese)'),
                ])->columnSpan(['lg' => 2]),
                Section::make('Settings')->schema([
                    Select::make('category')->options(['leadership' => 'Founder & leadership', 'booking' => 'Booking & communication', 'operations' => 'Trip operations', 'field' => 'Field partners'])->required(),
                    TextInput::make('location')->maxLength(160),
                    TagsInput::make('languages'),
                    TextInput::make('profile_url')->url()->maxLength(2048),
                    TextInput::make('sort_order')->numeric()->default(0)->required(),
                    Toggle::make('is_featured')->label('Featured member'),
                    Toggle::make('is_sample')->label('Show sample marker')->helperText('Keep enabled until this is a confirmed real profile.'),
                    Toggle::make('is_active')->label('Published')->default(true),
                ])->columnSpan(['lg' => 1]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('portrait')
                    ->state(fn (TeamMember $record): ?string => $record->portrait
                        ? url(PublicSite::assetPath($record->portrait))
                        : null)
                    ->square(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('role.us')->label('Role'),
                TextColumn::make('category')->badge(),
                IconColumn::make('is_sample')->label('Sample')->boolean(),
                IconColumn::make('is_active')->label('Published')->boolean(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeamMembers::route('/'),
            'create' => Pages\CreateTeamMember::route('/create'),
            'edit' => Pages\EditTeamMember::route('/{record}/edit'),
        ];
    }
}
