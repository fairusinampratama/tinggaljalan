<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_code')->searchable(),
                TextColumn::make('name')->label('Customer')->searchable(),
                TextColumn::make('tourPackage.slug')->label('Package')->searchable(),
                TextColumn::make('destination.name')->searchable(),
                TextColumn::make('travel_date')->date()->sortable(),
                TextColumn::make('pax')->numeric()->sortable(),
                TextColumn::make('total')->money('IDR')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'new' => 'warning',
                        'contacted' => 'info',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'new' => 'New',
                    'contacted' => 'Contacted',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'completed' => 'Completed',
                ]),
                SelectFilter::make('destination_id')->relationship('destination', 'name')->label('Destination'),
                Filter::make('travel_date')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('travel_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('travel_date', '<=', $date))),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('open_whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn ($record): string => 'https://wa.me/'.preg_replace('/\D+/', '', (string) $record->whatsapp))
                    ->visible(fn ($record): bool => filled(preg_replace('/\D+/', '', (string) $record->whatsapp)))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }
}
