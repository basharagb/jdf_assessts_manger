<?php

namespace App\Http\Controllers;

use App\AnnualLease;
use App\Models\Market;
use App\Models\MarketExpansion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketExpansionController extends Controller
{
    public function index(Market $market): View
    {
        $market->load(['group', 'sections', 'expansions.section']);
        $firstYear = $market->lease_start_date->year;
        $lastYear = AnnualLease::lastYear($market);

        return view('markets.expansions', [
            'market' => $market,
            'firstYear' => $firstYear,
            'lastYear' => $lastYear,
            'defaultYear' => min($lastYear, max($firstYear, AnnualLease::currentYear($market))),
            'anniversaries' => AnnualLease::anniversaryLabels($market, $firstYear, $lastYear),
        ]);
    }

    public function store(Request $request, Market $market): RedirectResponse
    {
        $market->load('sections');
        $data = $request->validate([
            'market_section_id' => $market->hasSections() ? ['required', 'integer', Rule::in($market->sections->modelKeys())] : ['exclude'],
            'start_year' => ['required', 'integer', 'between:'.$market->lease_start_date->year.','.AnnualLease::lastYear($market)],
            'added_annual_rent' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'added_area' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'market_section_id' => 'القسم', 'start_year' => 'سنة بدء التوسعة',
            'added_annual_rent' => 'المبلغ المضاف', 'added_area' => 'المساحة المضافة',
        ]);
        $market->expansions()->create($data);

        return redirect()->route('markets.expansions.index', $market)->with('success', 'تمت إضافة توسعة السوق.');
    }

    public function destroy(Market $market, MarketExpansion $expansion): RedirectResponse
    {
        abort_unless($expansion->market_id === $market->id, 404);
        $expansion->delete();

        return redirect()->route('markets.expansions.index', $market)->with('success', 'تم حذف التوسعة.');
    }
}
