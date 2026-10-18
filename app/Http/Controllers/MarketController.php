<?php

namespace App\Http\Controllers;

use App\AnnualLease;
use App\Models\Market;
use App\Models\MarketGroup;
use App\Models\MarketSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MarketController extends Controller
{
    public function chooseGroup(): View
    {
        return view('markets.choose-group', ['groups' => MarketGroup::withCount('markets')->orderBy('id')->get()]);
    }

    public function index(Request $request): View
    {
        $query = Market::query()->with(['group', 'sections', 'expansions', 'rentPeriods'])->latest();
        $search = trim((string) $request->query('search', ''));
        $groupId = $request->integer('group_id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('lessor', 'like', "%{$search}%")
                    ->orWhereHas('group', fn ($groupQuery) => $groupQuery->where('name', 'like', "%{$search}%"));
            });
        }
        if ($groupId > 0) {
            $query->where('market_group_id', $groupId);
        }

        return view('markets.index', [
            'markets' => $query->paginate(12)->withQueryString(),
            'groups' => MarketGroup::withCount('markets')->orderBy('id')->get(),
            'search' => $search,
            'selectedGroupId' => $groupId ?: null,
        ]);
    }

    public function create(MarketGroup $marketGroup): View
    {
        return view('markets.form', [
            'market' => new Market,
            'marketGroup' => $marketGroup,
            'groups' => MarketGroup::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, MarketGroup $marketGroup): RedirectResponse
    {
        [$data, $sections] = $this->validatedData($request, true);
        $data['market_group_id'] = $marketGroup->id;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_demo'] = $request->boolean('is_demo');
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('markets', 'public');
        }
        $market = DB::transaction(function () use ($data, $sections): Market {
            $market = Market::create($data);
            $this->syncSections($market, $sections);

            return $market;
        });

        return redirect()->route('markets.show', $market)->with('success', 'تمت إضافة السوق بنجاح.');
    }

    /**
     * The calculator range runs between anniversaries: from 2025 to 2026 is the single rental year that starts in 2025.
     */
    public function show(Request $request, Market $market): View
    {
        $market->load(['group', 'sections', 'expansions', 'rentPeriods']);
        $firstYear = AnnualLease::firstYear($market);
        $lastYear = AnnualLease::lastYear($market);
        $defaultYear = min($lastYear, max($firstYear, AnnualLease::currentYear($market)));
        $data = $request->validate([
            'from' => ['nullable', 'integer', 'between:'.$firstYear.','.max($firstYear, $lastYear)],
            'to' => ['nullable', 'integer', 'between:'.($firstYear + 1).','.max($firstYear, $lastYear) + 1],
        ]);
        $from = (int) ($data['from'] ?? $defaultYear);
        $to = (int) ($data['to'] ?? $defaultYear + 1);
        $rangeError = $from >= $to ? 'يجب أن تكون سنة النهاية بعد سنة البداية. مثال: من 2025 إلى 2026 = سنة إيجار واحدة.' : null;
        $rows = $rangeError ? [] : AnnualLease::ledger($market, $from, $to - 1);
        $totals = AnnualLease::totals($rows);
        $anniversaries = AnnualLease::anniversaryLabels($market, $firstYear, $lastYear + 1);
        $currentRents = $market->displayedRentBySection();
        $currentExpansionRents = $market->displayedRentBySection('expansion');

        return view('markets.show', compact('market', 'from', 'to', 'firstYear', 'lastYear', 'rangeError', 'rows', 'totals', 'anniversaries', 'currentRents', 'currentExpansionRents'));
    }

    public function edit(Market $market): View
    {
        return view('markets.form', [
            'market' => $market->load('sections'),
            'marketGroup' => $market->group,
            'groups' => MarketGroup::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Market $market): RedirectResponse
    {
        $market->load('sections');
        [$data, $sections] = $this->validatedData($request, false, $market);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_demo'] = $request->boolean('is_demo');
        if ($request->hasFile('image')) {
            if ($market->image_path) {
                Storage::disk('public')->delete($market->image_path);
            }
            $data['image_path'] = $request->file('image')->store('markets', 'public');
        }
        DB::transaction(function () use ($market, $data, $sections): void {
            $market->update($data);
            $this->syncSections($market, $sections);
        });

        return redirect()->route('markets.show', $market)->with('success', 'تم حفظ تعديلات السوق.');
    }

    public function destroy(Market $market): RedirectResponse
    {
        $group = $market->group;
        if ($market->image_path) {
            Storage::disk('public')->delete($market->image_path);
        }
        $market->delete();

        return redirect()->route('groups.show', $group)->with('success', 'تم حذف السوق.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: ?list<array{id?: ?int, name: string, area: string, annual_rent: string}>}
     */
    private function validatedData(Request $request, bool $requireImage, ?Market $market = null): array
    {
        $sectionCount = (int) $request->input('section_count', 1);
        $isSplit = in_array($sectionCount, [2, 3], true);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'section_count' => ['nullable', 'integer', 'in:1,2,3'],
            'area' => $isSplit ? ['exclude'] : ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'annual_rent' => $isSplit ? ['exclude'] : ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'sections' => $isSplit ? ['required', 'array', 'size:'.$sectionCount] : ['exclude'],
            'sections.*.id' => $isSplit ? ['nullable', 'integer', Rule::in($market?->sections->modelKeys() ?? [])] : ['exclude'],
            'sections.*.name' => $isSplit ? ['required', 'string', 'max:80', 'distinct'] : ['exclude'],
            'sections.*.area' => $isSplit ? ['required', 'numeric', 'min:0.01', 'max:9999999999.99'] : ['exclude'],
            'sections.*.annual_rent' => $isSplit ? ['required', 'numeric', 'min:0', 'max:999999999999.99'] : ['exclude'],
            'lessor' => ['required', 'string', 'max:180'],
            'lease_start_date' => ['required', 'date_format:Y-m-d'],
            'lease_end_year' => ['nullable', 'integer', 'min:1900', 'max:9999'],
            'market_group_id' => ['nullable', 'exists:market_groups,id'],
            'description' => ['nullable', 'string', 'max:3000'],
            'image' => [$requireImage ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'name' => 'اسم السوق', 'area' => 'المساحة', 'lessor' => 'الجهة المؤجرة', 'section_count' => 'تكوين السوق',
            'annual_rent' => 'قيمة الإيجار السنوي', 'lease_start_date' => 'تاريخ بداية الإيجار',
            'lease_end_date' => 'تاريخ نهاية العقد', 'image' => 'صورة السوق', 'description' => 'ملاحظات',
            'sections.*.name' => 'اسم القسم', 'sections.*.area' => 'مساحة القسم', 'sections.*.annual_rent' => 'إيجار القسم',
        ]);

        $start = Carbon::parse($validated['lease_start_date']);
        if ($start->lt(AnnualLease::earliestDate()) && $validated['lease_start_date'] !== $market?->lease_start_date?->toDateString()) {
            throw ValidationException::withMessages(['lease_start_date' => 'يمكن إدخال الإيجار بأثر رجعي حتى خمس سنوات فقط.']);
        }
        $endYear = $validated['lease_end_year'] ?? null;
        if ($endYear !== null && (int) $endYear <= $start->year) {
            throw ValidationException::withMessages(['lease_end_year' => 'اختر سنة نهاية بعد سنة بداية العقد.']);
        }
        $validated['lease_end_date'] = $endYear === null ? null : $start->copy()->addYearsNoOverflow((int) $endYear - $start->year)->toDateString();
        if ($market !== null && $market->rentPeriods()->exists()) {
            if ($start->toDateString() !== $market->lease_start_date->toDateString()) {
                throw ValidationException::withMessages(['lease_start_date' => 'راجع فترات الإيجار المسجلة واحذفها قبل تغيير بداية العقد.']);
            }
            if ($endYear !== null && $market->rentPeriods()->where(fn ($query) => $query->where('start_year', '>=', $endYear)->orWhere('end_year', '>', $endYear))->exists()) {
                throw ValidationException::withMessages(['lease_end_year' => 'توجد فترات إيجار تتجاوز نهاية العقد المختارة.']);
            }
        }

        $sections = null;
        if ($isSplit) {
            $sections = array_values($validated['sections']);
            $validated['area'] = array_sum(array_map(fn (array $section): int => (int) round($section['area'] * 100), $sections)) / 100;
            $validated['annual_rent'] = array_sum(array_map(fn (array $section): int => (int) round($section['annual_rent'] * 100), $sections)) / 100;
        }
        if ($market !== null && $market->hasSections()) {
            $keptIds = $sections === null ? [] : array_map('intval', array_filter(array_column($sections, 'id')));
            $removedIds = array_diff($market->sections->modelKeys(), $keptIds);
            if ($removedIds !== [] && $market->rentPeriods()->whereIn('market_section_id', $removedIds)->exists()) {
                throw ValidationException::withMessages(['section_count' => $sections === null
                    ? 'احذف فترات إيجار الأقسام قبل تحويل السوق إلى وحدة واحدة.'
                    : 'لا يمكن إزالة قسم له فترات إيجار مسجلة. احذف فتراته أولاً.']);
            }
        }
        unset($validated['image'], $validated['lease_end_year'], $validated['section_count'], $validated['sections']);

        return [$validated, $sections];
    }

    /**
     * Saves the market's sections in the submitted order. A single-unit market keeps no sections.
     *
     * @param  ?list<array{id?: ?int, name: string, area: string, annual_rent: string}>  $sections
     */
    private function syncSections(Market $market, ?array $sections): void
    {
        $existing = $market->sections()->get()->keyBy('id');
        $keptIds = [];
        foreach ($sections ?? [] as $index => $section) {
            $attributes = ['name' => $section['name'], 'area' => $section['area'], 'annual_rent' => $section['annual_rent'], 'sort_order' => $index];
            $model = $existing->get((int) ($section['id'] ?? 0));
            if ($model !== null) {
                $model->update($attributes);
            } else {
                $model = $market->sections()->create($attributes);
            }
            $keptIds[] = $model->id;
        }
        MarketSection::query()->where('market_id', $market->id)->whereKeyNot($keptIds)->delete();
        $market->unsetRelation('sections');
        if ($existing->isEmpty() && $sections !== null) {
            $this->distributeMarketPeriods($market);
        }
    }

    /**
     * When a single-unit market is split, each recorded period is shared between the sections
     * in proportion to their base rent, so the market totals stay unchanged.
     */
    private function distributeMarketPeriods(Market $market): void
    {
        $sections = $market->sections()->get()->values();
        $totalRent = $sections->sum(fn (MarketSection $section): float => (float) $section->annual_rent);
        foreach ($market->rentPeriods()->whereNull('market_section_id')->get() as $period) {
            $remaining = (int) round((float) $period->annual_rent * 100);
            $periodCents = $remaining;
            foreach ($sections as $index => $section) {
                $weight = $totalRent > 0 ? (float) $section->annual_rent / $totalRent : 1 / $sections->count();
                $share = $index === $sections->count() - 1 ? $remaining : (int) round($periodCents * $weight);
                $remaining -= $share;
                $market->rentPeriods()->create([
                    'market_section_id' => $section->id, 'start_year' => $period->start_year, 'end_year' => $period->end_year,
                    'annual_rent' => $share / 100, 'status' => $period->status, 'notes' => $period->notes,
                ]);
            }
            $period->delete();
        }
    }
}
