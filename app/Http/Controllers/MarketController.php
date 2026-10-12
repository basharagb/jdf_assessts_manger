<?php

namespace App\Http\Controllers;

use App\AnnualLease;
use App\Models\Market;
use App\Models\MarketGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
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
        $query = Market::query()->with(['group', 'rentPeriods'])->latest();
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
        $data = $this->validatedData($request, true);
        $data['market_group_id'] = $marketGroup->id;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_demo'] = $request->boolean('is_demo');
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('markets', 'public');
        }
        $market = Market::create($data);

        return redirect()->route('markets.show', $market)->with('success', 'تمت إضافة السوق بنجاح.');
    }

    public function show(Request $request, Market $market): View
    {
        $market->load(['group', 'rentPeriods']);
        $firstYear = AnnualLease::firstYear($market);
        $lastYear = AnnualLease::lastYear($market);
        $defaultYear = min($lastYear, max($firstYear, AnnualLease::currentYear($market)));
        $data = $request->validate([
            'from' => ['nullable', 'integer', 'between:'.$firstYear.','.max($firstYear, $lastYear)],
            'to' => ['nullable', 'integer', 'between:'.$firstYear.','.max($firstYear, $lastYear)],
        ]);
        $from = (int) ($data['from'] ?? $defaultYear);
        $to = (int) ($data['to'] ?? $defaultYear);
        $rangeError = $from > $to ? 'يجب أن تكون سنة البداية قبل سنة النهاية أو مساوية لها.' : null;
        $rows = $rangeError ? [] : AnnualLease::ledger($market, $from, $to);
        $totals = AnnualLease::totals($rows);
        return view('markets.show', compact('market', 'from', 'to', 'firstYear', 'lastYear', 'rangeError', 'rows', 'totals'));
    }

    public function edit(Market $market): View
    {
        return view('markets.form', [
            'market' => $market,
            'marketGroup' => $market->group,
            'groups' => MarketGroup::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Market $market): RedirectResponse
    {
        $data = $this->validatedData($request, false, $market);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_demo'] = $request->boolean('is_demo');
        if ($request->hasFile('image')) {
            if ($market->image_path) {
                Storage::disk('public')->delete($market->image_path);
            }
            $data['image_path'] = $request->file('image')->store('markets', 'public');
        }
        $market->update($data);

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

    private function validatedData(Request $request, bool $requireImage, ?Market $market = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'area' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'lessor' => ['required', 'string', 'max:180'],
            'annual_rent' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'lease_start_date' => ['required', 'date_format:Y-m-d'],
            'lease_end_year' => ['nullable', 'integer', 'min:1900', 'max:9999'],
            'market_group_id' => ['nullable', 'exists:market_groups,id'],
            'description' => ['nullable', 'string', 'max:3000'],
            'image' => [$requireImage ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'name' => 'اسم السوق', 'area' => 'المساحة', 'lessor' => 'الجهة المؤجرة',
            'annual_rent' => 'قيمة الإيجار السنوي', 'lease_start_date' => 'تاريخ بداية الإيجار',
            'lease_end_date' => 'تاريخ نهاية العقد', 'image' => 'صورة السوق', 'description' => 'ملاحظات',
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
            if ($endYear !== null && $market->rentPeriods()->where(fn ($query) => $query->where('start_year', '>=', $endYear)->orWhere('end_year', '>=', $endYear))->exists()) {
                throw ValidationException::withMessages(['lease_end_year' => 'توجد فترات إيجار تتجاوز نهاية العقد المختارة.']);
            }
        }
        unset($validated['image'], $validated['lease_end_year']);

        return $validated;
    }
}
