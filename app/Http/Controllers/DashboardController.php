<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\MarketGroup;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $markets = Market::query()->with('rentPeriods')->get();
        $activeMarkets = $markets->where('is_active', true);
        $groups = MarketGroup::query()->withCount('markets')->orderBy('id')->get();
        foreach ($groups as $group) {
            $group->setAttribute('active_rent_sum', $activeMarkets
                ->where('market_group_id', $group->id)
                ->sum(fn (Market $market) => $market->currentAnnualRent()));
        }

        return view('dashboard.index', [
            'groups' => $groups,
            'marketCount' => $markets->count(),
            'activeCount' => $activeMarkets->count(),
            'demoCount' => $markets->where('is_demo', true)->count(),
            'annualRent' => $activeMarkets->sum(fn (Market $market) => $market->currentAnnualRent()),
            'renewalsSoon' => Market::whereNotNull('lease_end_date')
                ->whereBetween('lease_end_date', [today(), Carbon::today()->addDays(90)])
                ->where('is_active', true)->count(),
            'recentMarkets' => Market::with(['group', 'rentPeriods'])->latest()->take(5)->get(),
        ]);
    }
}
