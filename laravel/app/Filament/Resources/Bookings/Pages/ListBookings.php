<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Support\BookingWorkflow;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function mount(): void
    {
        parent::mount();

        if (! array_key_exists((string) $this->activeTab, $this->getTabs())) {
            $this->activeTab = BookingWorkflow::NEEDS_ACTION;
        }
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return BookingWorkflow::NEEDS_ACTION;
    }

    public function getTabs(): array
    {
        return [
            BookingWorkflow::NEEDS_ACTION => Tab::make('Needs action')
                ->modifyQueryUsing(fn (Builder $query): Builder => BookingWorkflow::applyCategory($query, BookingWorkflow::NEEDS_ACTION)),
            BookingWorkflow::AWAITING_PAYMENT => Tab::make('Awaiting payment')
                ->modifyQueryUsing(fn (Builder $query): Builder => BookingWorkflow::applyCategory($query, BookingWorkflow::AWAITING_PAYMENT)),
            BookingWorkflow::CONFIRMED_TRIPS => Tab::make('Confirmed trips')
                ->modifyQueryUsing(fn (Builder $query): Builder => BookingWorkflow::applyCategory($query, BookingWorkflow::CONFIRMED_TRIPS)),
            BookingWorkflow::CLOSED => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $query): Builder => BookingWorkflow::applyCategory($query, BookingWorkflow::CLOSED)),
            'all' => Tab::make('All'),
        ];
    }
}
