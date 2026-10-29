<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все'),
            'new' => Tab::make('Новые')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::New)),
            'in_progress' => Tab::make('В работе')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    OrderStatus::Confirmed, OrderStatus::Ordered, OrderStatus::Ready, OrderStatus::Shipped,
                ])),
            'closed' => Tab::make('Закрытые')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [OrderStatus::Completed, OrderStatus::Cancelled])),
        ];
    }
}
