<?php

namespace App\Filament\Resources\Vouchers\Tables;

use App\Models\Voucher;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'bookings as active_redemptions_count' => fn (Builder $query): Builder => $query->where('status', '!=', 'cancelled'),
            ]))
            ->columns([
                TextColumn::make('code')->searchable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('discount_type')->badge()->searchable(),
                TextColumn::make('discount_value')->numeric()->sortable(),
                TextColumn::make('currency')
                    ->label('Fixed currency')
                    ->badge()
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('eligible_currencies')
                    ->label('Eligible currencies')
                    ->state(fn (Voucher $record): string => $record->discount_type === 'fixed'
                        ? ($record->currency ?: '-')
                        : (collect($record->allowed_currencies ?? [])->join(', ') ?: '-')),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->dateTime()->sortable(),
                TextColumn::make('active_redemptions_count')
                    ->label('Used')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('usage_limit')
                    ->label('Limit')
                    ->state(fn (Voucher $record): string => (string) ($record->usage_limit ?? 'Unlimited'))
                    ->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('discount_type')->options([
                    'percent' => 'Percent',
                    'fixed' => 'Fixed amount',
                ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
