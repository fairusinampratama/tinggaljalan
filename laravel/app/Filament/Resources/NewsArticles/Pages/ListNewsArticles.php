<?php

namespace App\Filament\Resources\NewsArticles\Pages;

use App\Filament\Resources\NewsArticles\NewsArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNewsArticles extends ListRecords
{
    protected static string $resource = NewsArticleResource::class;

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
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'draft')),
            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'published')),
            'featured' => Tab::make('Featured')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_featured', true)),
        ];
    }
}
