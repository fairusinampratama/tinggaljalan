<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Schemas\AboutPageForm;
use App\Models\AboutPage;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AboutPageContent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'About Us';

    protected static ?string $navigationLabel = 'About Page';

    protected static ?string $title = 'About Page';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.about-page-content';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(AboutPage::query()->first()?->toArray() ?? []);
    }

    public function form(Schema $schema): Schema
    {
        return AboutPageForm::configure($schema)->statePath('data');
    }

    public function save(): void
    {
        $page = AboutPage::query()->first() ?? new AboutPage(['seed_key' => 'default-about-page']);
        if (blank($page->seed_key)) {
            $page->seed_key = 'default-about-page';
        }

        $page->fill($this->form->getState())->save();

        Notification::make()->title('About page updated')->success()->send();
    }

    public function refreshRelatedPreviews(): void
    {
        Notification::make()->title('Team and milestone previews refreshed')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewAboutPage')
                ->label('View About page')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (): string => route('about.show'))
                ->visible(fn (): bool => AboutPage::query()->published()->exists())
                ->openUrlInNewTab(),
        ];
    }
}
