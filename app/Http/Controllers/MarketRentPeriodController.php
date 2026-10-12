<?php

namespace App\Http\Controllers;

use App\AnnualLease;
use App\Models\Market;
use App\Models\MarketRentPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MarketRentPeriodController extends Controller
{
    private const STATUSES = ['paid', 'unpaid', 'vacant'];

    public function index(Market $market): View
    {
        return view('markets.rent-details', [
            'market' => $market->load('group'),
            'periods' => $market->rentPeriods()->orderBy('start_year')->get(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(Market $market): View
    {
        return $this->form($market, new MarketRentPeriod([
            'start_year' => AnnualLease::firstYear($market),
            'status' => 'paid',
        ]));
    }

    public function store(Request $request, Market $market): RedirectResponse
    {
        DB::transaction(function () use ($request, $market): void {
            $lockedMarket = Market::query()->lockForUpdate()->findOrFail($market->id);
            $lockedMarket->rentPeriods()->create($this->validatedPeriod($request, $lockedMarket));
        });

        return redirect()->route('markets.rent-details.index', $market)->with('success', 'تمت إضافة فترة الإيجار.');
    }

    public function edit(Market $market, MarketRentPeriod $rentPeriod): View
    {
        $this->ensurePeriodBelongsToMarket($market, $rentPeriod);

        return $this->form($market, $rentPeriod);
    }

    public function update(Request $request, Market $market, MarketRentPeriod $rentPeriod): RedirectResponse
    {
        $this->ensurePeriodBelongsToMarket($market, $rentPeriod);
        DB::transaction(function () use ($request, $market, $rentPeriod): void {
            $lockedMarket = Market::query()->lockForUpdate()->findOrFail($market->id);
            $lockedPeriod = $lockedMarket->rentPeriods()->lockForUpdate()->findOrFail($rentPeriod->id);
            $lockedPeriod->update($this->validatedPeriod($request, $lockedMarket, $lockedPeriod));
        });

        return redirect()->route('markets.rent-details.index', $market)->with('success', 'تم تحديث فترة الإيجار.');
    }

    public function destroy(Market $market, MarketRentPeriod $rentPeriod): RedirectResponse
    {
        $this->ensurePeriodBelongsToMarket($market, $rentPeriod);
        $rentPeriod->delete();

        return redirect()->route('markets.rent-details.index', $market)->with('success', 'تم حذف فترة الإيجار.');
    }

    private function form(Market $market, MarketRentPeriod $rentPeriod): View
    {
        return view('markets.rent-period-form', [
            'market' => $market->load('group'),
            'rentPeriod' => $rentPeriod,
            'statusLabels' => $this->statusLabels(),
            'pickerMin' => min(AnnualLease::firstYear($market), $rentPeriod->start_year ?? AnnualLease::firstYear($market)),
            'pickerMax' => AnnualLease::lastYear($market),
        ]);
    }

    private function validatedPeriod(Request $request, Market $market, ?MarketRentPeriod $rentPeriod = null): array
    {
        $data = $request->validate([
            'start_year' => ['required', 'integer', 'between:1900,9998'],
            'end_year' => ['nullable', 'integer', 'between:1900,9998'],
            'annual_rent' => ['required_unless:status,vacant', 'nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['start_year'] = (int) $data['start_year'];
        $data['end_year'] = isset($data['end_year']) ? (int) $data['end_year'] : null;
        $leaseStart = $market->lease_start_date->year;
        $leaseEnd = AnnualLease::lastYear($market);
        if ($data['start_year'] < $leaseStart || $data['start_year'] > $leaseEnd) {
            throw ValidationException::withMessages(['start_year' => 'يجب أن تقع سنة الإيجار ضمن مدة عقد السوق.']);
        }
        if (AnnualLease::anniversary($market, $data['start_year'])->lt(AnnualLease::earliestDate()) && $data['start_year'] !== $rentPeriod?->start_year) {
            throw ValidationException::withMessages(['start_year' => 'يمكن التسجيل بأثر رجعي حتى خمس سنوات فقط.']);
        }
        if ($data['end_year'] !== null && ($data['end_year'] < $data['start_year'] || $data['end_year'] > $leaseEnd)) {
            throw ValidationException::withMessages(['end_year' => 'اختر سنة نهاية ضمن العقد وبعد سنة البداية أو مساوية لها.']);
        }

        $rangeEnd = $data['end_year'] ?? 9999;
        $overlaps = $market->rentPeriods()
            ->where('start_year', '<=', $rangeEnd)
            ->where(fn ($query) => $query->whereNull('end_year')->orWhere('end_year', '>=', $data['start_year']))
            ->when($rentPeriod, fn ($query) => $query->whereKeyNot($rentPeriod->id))
            ->exists();
        if ($overlaps) {
            throw ValidationException::withMessages(['start_year' => 'تتداخل هذه الفترة مع فترة إيجار مسجلة. عدّل الفترة الموجودة أو اختر سنوات مختلفة.']);
        }

        $data['annual_rent'] = $data['status'] === 'vacant' ? 0 : $data['annual_rent'];

        return $data;
    }

    private function ensurePeriodBelongsToMarket(Market $market, MarketRentPeriod $rentPeriod): void
    {
        abort_unless($rentPeriod->market_id === $market->id, 404);
    }

    private function statusLabels(): array
    {
        return ['paid' => 'مدفوع', 'unpaid' => 'مستحق غير مدفوع', 'vacant' => 'إخلاء — لا يستحق إيجار'];
    }
}
