<?php

namespace App\Http\Controllers;

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
            'periods' => $market->rentPeriods()->orderBy('start_month')->get(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(Market $market): View
    {
        return $this->form($market, new MarketRentPeriod([
            'start_month' => $market->lease_start_date->format('Y-m'),
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
            'pickerMin' => $market->lease_start_date->format('Y-m'),
            'pickerMax' => $market->lease_end_date?->format('Y-m') ?? now()->addYears(10)->format('Y-m'),
        ]);
    }

    private function validatedPeriod(Request $request, Market $market, ?MarketRentPeriod $rentPeriod = null): array
    {
        $data = $request->validate([
            'start_month' => ['required', 'date_format:Y-m'],
            'end_month' => ['nullable', 'date_format:Y-m'],
            'monthly_rent' => ['required_unless:status,vacant', 'nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $leaseStart = $market->lease_start_date->format('Y-m');
        $leaseEnd = $market->lease_end_date?->format('Y-m');
        if ($data['start_month'] < $leaseStart || ($leaseEnd && $data['start_month'] > $leaseEnd)) {
            throw ValidationException::withMessages(['start_month' => 'يجب أن يقع شهر البداية ضمن مدة عقد السوق.']);
        }
        if ($data['end_month'] !== null && $data['end_month'] < $data['start_month']) {
            throw ValidationException::withMessages(['end_month' => 'يجب أن يكون شهر النهاية بعد شهر البداية أو مساوياً له.']);
        }
        if ($leaseEnd && ($data['end_month'] ?? $leaseEnd) > $leaseEnd) {
            throw ValidationException::withMessages(['end_month' => 'لا يمكن أن تتجاوز الفترة نهاية عقد السوق.']);
        }

        $rangeEnd = $data['end_month'] ?? '9999-12';
        $overlaps = $market->rentPeriods()
            ->where('start_month', '<=', $rangeEnd)
            ->where(fn ($query) => $query->whereNull('end_month')->orWhere('end_month', '>=', $data['start_month']))
            ->when($rentPeriod, fn ($query) => $query->whereKeyNot($rentPeriod->id))
            ->exists();
        if ($overlaps) {
            throw ValidationException::withMessages(['start_month' => 'تتداخل هذه الفترة مع فترة إيجار مسجلة. عدّل الفترة الموجودة أو اختر أشهرًا مختلفة.']);
        }

        $data['monthly_rent'] = $data['status'] === 'vacant' ? 0 : $data['monthly_rent'];

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
