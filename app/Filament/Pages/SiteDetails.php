<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class SiteDetails extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static string|\UnitEnum|null $navigationGroup = 'Site Management';
    protected static ?string $navigationLabel = 'Site Details';
    protected static ?string $title = 'Site Details';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.site-details';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = SiteSetting::first();
        if ($setting) {
            $this->form->fill($setting->toArray());
        } else {
            $this->form->fill();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Brand Assets')
                    ->schema([
                        FileUpload::make('logo_url')
                            ->label('Logo')
                            ->image()
                            ->imagePreviewHeight('250')
                            ->directory('admin/site')
                            ->disk('public')
                            ->helperText('Upload the site logo. It will be displayed in the header and footer.'),
                    ]),

                Section::make('Contact Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('whatsapp_number')
                                ->label('WhatsApp Number')
                                ->tel()
                                ->regex('/^\+?[1-9]\d{1,14}$/')
                                ->helperText('Use E.164 format, e.g., +6281234567890.'),

                            TextInput::make('contact_email')
                                ->label('Contact Email')
                                ->email()
                                ->helperText('The primary email address for customer inquiries.'),
                        ]),

                        Textarea::make('business_address')
                            ->label('Business Address')
                            ->rows(3)
                            ->helperText('The physical address of the business.'),

                        TextInput::make('google_maps_url')
                            ->label('Google Maps URL')
                            ->url()
                            ->helperText('URL linking to the business location on Google Maps.'),
                    ]),

                Section::make('Homepage Hero')
                    ->schema([
                        Toggle::make('hero_autoplay_enabled')
                            ->label('Enable hero autoplay')
                            ->default(false)
                            ->helperText('When enabled, visitors can still pause the carousel. Reduced-motion preferences always disable autoplay.'),
                        TextInput::make('hero_autoplay_interval')
                            ->label('Autoplay interval (milliseconds)')
                            ->numeric()
                            ->default(8000)
                            ->minValue(5000)
                            ->maxValue(15000)
                            ->required()
                            ->helperText('Allowed range: 5,000 to 15,000 milliseconds.'),
                    ])
                    ->columns(2),

                Section::make('Service Settings')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('service_hours.id')
                                ->label('Service Hours (Indonesian)')
                                ->placeholder('e.g. Senin - Jumat, 09:00 - 17:00'),
                            TextInput::make('service_hours.us')
                                ->label('Service Hours (English)')
                                ->placeholder('e.g. Monday - Friday, 09:00 - 17:00'),
                            TextInput::make('service_hours.cn')
                                ->label('Service Hours (Chinese)')
                                ->placeholder('e.g. 星期一 - 星期五, 09:00 - 17:00'),
                        ]),

                        TagsInput::make('service_areas')
                            ->label('Service Areas')
                            ->helperText('Areas where your services are available.'),

                        TagsInput::make('trust_badges')
                            ->label('Footer Trust Badges')
                            ->helperText('Short labels for trust badges displayed in the footer.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Ensure E.164 format for WhatsApp if provided (strip non-digits and add + if needed)
        // Since we have a regex validation, we assume it's mostly correct, but let's strip spaces/dashes if any exist.
        if (!empty($data['whatsapp_number'])) {
            $number = preg_replace('/[^\d+]/', '', $data['whatsapp_number']);
            if (!str_starts_with($number, '+') && str_starts_with($number, '62')) {
                $number = '+' . $number;
            }
            $data['whatsapp_number'] = $number;
        }

        SiteSetting::updateOrCreate(['id' => 1], $data);

        Notification::make()
            ->title('Site details updated')
            ->success()
            ->send();
    }
}
