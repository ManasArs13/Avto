<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Order::whereDate('created_at', today())->where('status', '!=', OrderStatus::Cancelled);

        return [
            Stat::make('Новые заказы', Order::where('status', OrderStatus::New)->count())
                ->description('Ждут обработки')
                ->color('warning'),
            Stat::make('Продажи сегодня', number_format((float) (clone $today)->sum('total'), 0, ',', ' ').' ₽')
                ->description((clone $today)->count().' заказов'),
            Stat::make('Товаров в каталоге', number_format(Product::count(), 0, ',', ' ')),
            Stat::make('Предложений поставщиков', number_format(Offer::count(), 0, ',', ' ')),
        ];
    }
}
