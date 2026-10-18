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
        $market->load(['group', 'sections', 'expansions', 'rentPeriods' => fn ($query) => $query->orderBy('start_year')]);

        return view('markets.rent-details', [
            'market' => $market,
            'periodGroups' => $market->hasSections()
                ? $market->sections->map(fn ($section): array => ['section' => $section, 'periods' => $market->rentPeriods->where('market_section_id', $section->id)->values()])
                : collect([['section' => null, 'periods' => $market->rentPeriods]]),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(Request $request, Market $market): View
    {
        $market->load(['sections', 'expansions', 'rentPeriods']);
        $section = $market->hasSections() ? ($market->sections->firstWhere('id', $request->integer('section')) ?? $market->sections->first()) : null;
        $latestEnd = $market->rentPeriods->where('market_section_id', $section?->id)->max('end_year');
        $startYear = min(AnnualLease::lastYear($market), max(AnnualLease::firstYear($market), (int) $latestEnd));

        return $this->form($market, new MarketRentPeriod([
            'market_section_id' => $section?->id,
            'start_year' => $startYear,
            'end_year' => $startYear + 1,
            'annual_rent' => $section?->annual_rent ?? $market->annual_rent,
            'status' => 'paid',
        ]));
    }

    public function store(Request $request, Market $market): RedirectResponse
    {
        DB::transaction(function () use ($request, $market): void {
            $lockedMarket = Market::query()->with('sections')->lockForUpdate()->findOrFail($market->id);
            $lockedMarket->rentPeriods()->create($this->validatedPeriod($request, $lockedMarket));
        });

        return redirect()->route('markets.rent-details.index', $market)->with('success', 'تمت إضافة فترة الإيجار.');
    }

    public function edit(Market $market, MarketRentPeriod $rentPeriod): View
    {
        $this->ensurePeriodBelongsToMarket($market, $rentPeriod);

        return $this->form($market->load('sections'), $rentPeriod);
    }

    public function update(Request $request, Market $market, MarketRentPeriod $rentPeriod): RedirectResponse
    {
        $this->ensurePeriodBelongsToMarket($market, $rentPeriod);
        DB::transaction(function () use ($request, $market, $rentPeriod): void {
            $lockedMarket = Market::query()->with('sections')->lockForUpdate()->findOrFail($market->id);
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
        $pickerMin = min(AnnualLease::firstYear($market), $rentPeriod->start_year ?? AnnualLease::firstYear($market));
        $pickerMax = AnnualLease::lastYear($market);

        return view('markets.rent-period-form', [
            'market' => $market->load('group'),
            'rentPeriod' => $rentPeriod,
            'statusLabels' => $this->statusLabels(),
            'pickerMin' => $pickerMin,
            'pickerMax' => $pickerMax,
            'anniversaries' => AnnualLease::anniversaryLabels($market, $pickerMin, $pickerMax + 1),
        ]);
    }

    /**
     * @return array{market_section_id: ?int, start_year: int, end_year: ?int, annual_rent: float|string|int, expansion_rent: float|string|int|null, status: string, notes: ?string}
     */
    private function validatedPeriod(Request $request, Market $market, ?MarketRentPeriod $rentPeriod = null): array
    {
        $data = $request->validate([
            'market_section_id' => $market->hasSections() ? ['required', 'integer', Rule::in($market->sections->modelKeys())] : ['exclude'],
            'start_year' => ['required', 'integer', 'between:1900,9998'],
            'end_year' => ['nullable', 'integer', 'between:1901,9999'],
            'annual_rent' => ['required_unless:status,vacant', 'nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'expansion_rent' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['market_section_id' => 'القسم']);
        $data['market_section_id'] = isset($data['market_section_id']) ? (int) $data['market_section_id'] : null;
        $data['start_year'] = (int) $data['start_year'];
        $data['end_year'] = isset($data['end_year']) ? (int) $data['end_year'] : null;
        $leaseStart = $market->lease_start_date->year;
        $lastYear = AnnualLease::lastYear($market);
        if ($data['start_year'] < $leaseStart || $data['start_year'] > $lastYear) {
            throw ValidationException::withMessages(['start_year' => 'يجب أن تقع سنة الإيجار ضمن مدة عقد السوق.']);
        }
        if (AnnualLease::anniversary($market, $data['start_year'])->lt(AnnualLease::earliestDate()) && $data['start_year'] !== $rentPeriod?->start_year) {
            throw ValidationException::withMessages(['start_year' => 'يمكن التسجيل بأثر رجعي حتى خمس سنوات فقط.']);
        }
        if ($data['end_year'] !== null && ($data['end_year'] <= $data['start_year'] || $data['end_year'] > $lastYear + 1)) {
            throw ValidationException::withMessages(['end_year' => 'اختر سنة نهاية بعد سنة البداية وضمن مدة العقد. مثال: من 2022 إلى 2023 = سنة إيجار واحدة.']);
        }

        $overlapping = $market->rentPeriods()
            ->when($data['market_section_id'], fn ($query, int $sectionId) => $query->where('market_section_id', $sectionId), fn ($query) => $query->whereNull('market_section_id'))
            ->when($data['end_year'], fn ($query, int $endYear) => $query->where('start_year', '<', $endYear))
            ->where(fn ($query) => $query->whereNull('end_year')->orWhere('end_year', '>', $data['start_year']))
            ->when($rentPeriod, fn ($query) => $query->whereKeyNot($rentPeriod->id))
            ->orderBy('start_year')
            ->first();
        if ($overlapping !== null) {
            throw ValidationException::withMessages(['start_year' => 'تتداخل هذه الفترة مع الفترة المسجلة '.$overlapping->start_year.' ← '.($overlapping->end_year ?? 'مستمرة').'. يمكن أن تبدأ الفترة التالية في سنة نهاية الفترة السابقة.']);
        }

        $data['annual_rent'] = $data['status'] === 'vacant' ? 0 : $data['annual_rent'];
        $data['expansion_rent'] = $data['status'] === 'vacant' ? null : ($data['expansion_rent'] ?? null);

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
