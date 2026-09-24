<?php

namespace App\Filament\Resources\Vouchers\Tables;

use App\Models\Voucher;
use App\Support\PublicSite;
use App\Support\VoucherPromotionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                'tourPackages as tour_packages_count',
                'tourPackages as active_tour_packages_count' => fn (Builder $query): Builder => $query->active(),
            ]))
            ->columns([
                TextColumn::make('code')
                    ->description(fn (Voucher $record): string => $record->label)
                    ->searchable(['code', 'label'])
                    ->sortable(),
                TextColumn::make('discount')
                    ->state(fn (Voucher $record): string => self::discountLabel($record)),
                TextColumn::make('homepage_status')
                    ->label('Homepage status')
                    ->badge()
                    ->state(fn (Voucher $record): string => self::status($record)['label'])
                    ->color(fn (Voucher $record): string => self::status($record)['color'])
                    ->description(fn (Voucher $record): string => self::statusDescription($record))
                    ->wrap(),
                TextColumn::make('usage')
                    ->state(fn (Voucher $record): string => sprintf(
                        '%d / %s',
                        (int) $record->active_redemptions_count,
                        $record->usage_limit ?? 'Unlimited',
                    )),
            ])
            ->filters([
                SelectFilter::make('discount_type')->options([
                    'percent' => 'Percent',
                    'fixed' => 'Fixed amount',
                ]),
                TernaryFilter::make('is_active'),
                TernaryFilter::make('is_public')->label('Homepage promotion'),
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

    /** @return array<string, mixed> */
    private static function status(Voucher $record): array
    {
        return app(VoucherPromotionStatus::class)->forVoucher($record);
    }

    private static function statusDescription(Voucher $record): string
    {
        $status = self::status($record);

        if ($status['is_visible']) {
            return implode(' · ', $status['audiences']);
        }

        return $status['reason'];
    }

    private static function discountLabel(Voucher $record): string
    {
        if ($record->discount_type === 'fixed') {
            return PublicSite::formatMoney((float) $record->discount_value, $record->currency ?: 'IDR');
        }

        return rtrim(rtrim(number_format((float) $record->discount_value, 2, '.', ''), '0'), '.').'%';
    }
}
