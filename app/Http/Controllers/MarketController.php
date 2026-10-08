<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\MarketGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $contractStartMonth = $market->lease_start_date->format('Y-m');
        $contractEndMonth = $market->lease_end_date?->format('Y-m');
        $defaultMonth = now()->format('Y-m');
        if ($defaultMonth < $contractStartMonth) {
            $defaultMonth = $contractStartMonth;
        } elseif ($contractEndMonth !== null && $defaultMonth > $contractEndMonth) {
            $defaultMonth = $contractEndMonth;
        }

        $from = $request->query('from', $defaultMonth);
        $to = $request->query('to', $defaultMonth);
        $rangeError = null;
        $months = 0;
        $rentTotal = 0.0;
        $paidTotal = 0.0;
        $unpaidTotal = 0.0;
        $untrackedTotal = 0.0;
        $vacantMonths = 0;
        $rentPeriods = $market->rentPeriods()->orderBy('start_month')->get();
        $rentPeriodData = $rentPeriods->map(fn ($period) => [
            'start' => $period->start_month,
            'end' => $period->end_month,
            'rent' => (float) $period->monthly_rent,
            'status' => $period->status,
        ])->all();
        $validated = validator(['from' => $from, 'to' => $to], [
            'from' => ['required', 'date_format:Y-m'],
            'to' => ['required', 'date_format:Y-m'],
        ], [
            'from.date_format' => 'اختر شهر بداية صحيحاً.',
            'to.date_format' => 'اختر شهر نهاية صحيحاً.',
        ])->validate();
        $from = $validated['from'];
        $to = $validated['to'];

        if ($from > $to) {
            $rangeError = 'يجب أن يكون شهر البداية قبل شهر النهاية أو مساوياً له.';
        } else {
            $overlapStart = $from > $contractStartMonth ? $from : $contractStartMonth;
            $overlapEnd = $contractEndMonth !== null && $to > $contractEndMonth ? $contractEndMonth : $to;

            if ($overlapStart <= $overlapEnd) {
                [$startYear, $startMonth] = array_map('intval', explode('-', $overlapStart));
                [$endYear, $endMonth] = array_map('intval', explode('-', $overlapEnd));
                $months = (($endYear - $startYear) * 12) + ($endMonth - $startMonth) + 1;
                $cursor = $overlapStart;
                while ($cursor <= $overlapEnd) {
                    $period = $rentPeriods->first(fn ($candidate) => $candidate->start_month <= $cursor && ($candidate->end_month === null || $candidate->end_month >= $cursor));
                    if ($period === null) {
                        $rentTotal += (float) $market->monthly_rent;
                        $untrackedTotal += (float) $market->monthly_rent;
                    } elseif ($period->status === 'vacant') {
                        $vacantMonths++;
                    } else {
                        $rentTotal += (float) $period->monthly_rent;
                        if ($period->status === 'paid') {
                            $paidTotal += (float) $period->monthly_rent;
                        } else {
                            $unpaidTotal += (float) $period->monthly_rent;
                        }
                    }

                    [$cursorYear, $cursorMonth] = array_map('intval', explode('-', $cursor));
                    $cursor = sprintf('%04d-%02d', $cursorYear + ($cursorMonth === 12 ? 1 : 0), $cursorMonth === 12 ? 1 : $cursorMonth + 1);
                }
                $rentTotal = round($rentTotal, 2);
                $paidTotal = round($paidTotal, 2);
                $unpaidTotal = round($unpaidTotal, 2);
                $untrackedTotal = round($untrackedTotal, 2);
            }
        }

        return view('markets.show', compact(
            'market', 'from', 'to', 'months', 'rentTotal', 'rangeError', 'contractStartMonth', 'contractEndMonth',
            'rentPeriodData', 'rentPeriods', 'paidTotal', 'unpaidTotal', 'untrackedTotal', 'vacantMonths',
        ));
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
        $data = $this->validatedData($request, false);
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

    private function validatedData(Request $request, bool $requireImage): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'area' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'lessor' => ['required', 'string', 'max:180'],
            'monthly_rent' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'lease_start_date' => ['required', 'date'],
            'lease_end_date' => ['nullable', 'date', 'after_or_equal:lease_start_date'],
            'market_group_id' => ['nullable', 'exists:market_groups,id'],
            'description' => ['nullable', 'string', 'max:3000'],
            'image' => [$requireImage ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'name' => 'اسم السوق', 'area' => 'المساحة', 'lessor' => 'الجهة المؤجرة',
            'monthly_rent' => 'قيمة الإيجار الشهري', 'lease_start_date' => 'تاريخ بداية الإيجار',
            'lease_end_date' => 'تاريخ نهاية العقد', 'image' => 'صورة السوق', 'description' => 'ملاحظات',
        ]);

        unset($validated['image']);

        return $validated;
    }
}
