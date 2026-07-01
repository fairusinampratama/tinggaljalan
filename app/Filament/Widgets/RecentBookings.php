<?php

namespace App\Filament\Widgets;

use App\Filament\Support\BookingAttention;
use App\Filament\Support\BookingNextStep;
use App\Models\Booking;
use App\Support\PublicSite;
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
            ->description('Leads and upcoming trips that need follow-up or confirmation.')
            ->query(
                BookingAttention::applyNeedsAttention(Booking::query()->with(['tourPackage', 'destination']))
                    ->latest()
                    ->limit(8),
            )
            ->columns([
                TextColumn::make('booking_code')
                    ->label('Code')
                    ->searchable(),
                TextColumn::make('attention')
                    ->label('Attention')
                    ->badge()
                    ->state(fn (Booking $record): string => BookingAttention::status($record))
                    ->color(fn (Booking $record): string => BookingAttention::color($record)),
                TextColumn::make('next_step')
                    ->label('Next step')
                    ->badge()
                    ->state(fn (Booking $record): string => BookingNextStep::label($record))
                    ->color(fn (Booking $record): string => BookingNextStep::color($record))
                    ->description(fn (Booking $record): string => BookingNextStep::summary($record)),
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('package_title')
                    ->label('Package')
                    ->state(fn (Booking $record): string => $record->tourPackage
                        ? PublicSite::localized($record->tourPackage->title, 'us', $record->tourPackage->slug)
                        : '-')
                    ->wrap(),
                TextColumn::make('travel_date')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('total')
                    ->formatStateUsing(fn ($state, Booking $record): string => PublicSite::formatMoney((int) $state, $record->currency ?: 'IDR'))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'new' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->recordActions([

                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->url(fn (Booking $record): string => 'https://wa.me/'.preg_replace('/\D+/', '', (string) $record->whatsapp))
                    ->visible(fn (Booking $record): bool => filled(preg_replace('/\D+/', '', (string) $record->whatsapp)))
                    ->openUrlInNewTab(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No booking follow-up queued')
            ->emptyStateDescription('New leads, incomplete requests, and trips in the next 7 days will appear here.');
    }
}