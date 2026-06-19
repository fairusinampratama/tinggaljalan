<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentBookings extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Booking action queue')
            ->description('Latest bookings that usually need follow-up or confirmation.')
            ->query(
                Booking::query()
                    ->with(['tourPackage', 'destination'])
                    ->whereIn('status', ['new', 'contacted', 'confirmed'])
                    ->latest()
                    ->limit(8),
            )
            ->columns([
                TextColumn::make('booking_code')
                    ->label('Code')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('tourPackage.slug')
                    ->label('Package')
                    ->placeholder('-'),
                TextColumn::make('travel_date')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'new' => 'warning',
                        'contacted' => 'info',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
                    ->url(fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record])),
                Action::make('edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Booking $record): string => BookingResource::getUrl('edit', ['record' => $record])),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->url(fn (Booking $record): string => 'https://wa.me/'.preg_replace('/\D+/', '', (string) $record->whatsapp))
                    ->visible(fn (Booking $record): bool => filled(preg_replace('/\D+/', '', (string) $record->whatsapp)))
                    ->openUrlInNewTab(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No booking follow-up queued')
            ->emptyStateDescription('New, contacted, and confirmed bookings will appear here.');
    }
}
