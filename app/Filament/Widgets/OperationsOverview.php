<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\NewsArticles\NewsArticleResource;
use App\Filament\Resources\TourPackages\TourPackageResource;
use App\Models\Booking;
use App\Models\NewsArticle;
use App\Models\TourPackage;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 10;

    protected ?string $heading = 'Today at a glance';

    protected ?string $description = 'Fast signals for booking follow-up, upcoming travel, product coverage, and draft content.';

    protected function getStats(): array
    {
        $newBookings = Booking::query()->where('status', 'new')->count();
        $upcomingTrips = Booking::query()
            ->where('status', 'confirmed')
            ->whereDate('travel_date', '>=', now()->toDateString())
            ->count();
        $activePackages = TourPackage::query()->active()->count();
        $draftArticles = NewsArticle::query()->where('status', 'draft')->count();

        return [
            Stat::make('New bookings', $newBookings)
                ->description('Need availability review')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color($newBookings > 0 ? 'warning' : 'success')
                ->url(BookingResource::getUrl('index')),
            Stat::make('Upcoming trips', $upcomingTrips)
                ->description('Confirmed active trips')
                ->icon(Heroicon::OutlinedCalendarDateRange)
                ->color('info')
                ->url(BookingResource::getUrl('index')),
            Stat::make('Active packages', $activePackages)
                ->description('Visible on public routes')
                ->icon(Heroicon::OutlinedBriefcase)
                ->color('success')
                ->url(TourPackageResource::getUrl('index')),
            Stat::make('Draft articles', $draftArticles)
                ->description('Waiting to publish')
                ->icon(Heroicon::OutlinedNewspaper)
                ->color($draftArticles > 0 ? 'gray' : 'success')
                ->url(NewsArticleResource::getUrl('index')),
        ];
    }
}
