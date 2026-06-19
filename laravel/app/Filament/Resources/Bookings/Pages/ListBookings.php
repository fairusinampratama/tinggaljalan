<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'new' => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'new')),
            'contacted' => Tab::make('Contacted')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'contacted')),
            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'confirmed')),
            'upcoming' => Tab::make('Upcoming')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereIn('status', ['contacted', 'confirmed'])
                    ->whereDate('travel_date', '>=', now()->toDateString())),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'cancelled')),
        ];
    }
}
