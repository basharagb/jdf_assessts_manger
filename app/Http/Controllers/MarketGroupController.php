<?php

namespace App\Http\Controllers;

use App\Models\MarketGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketGroupController extends Controller
{
    public function index(): View
    {
        return view('groups.index', [
            'groups' => MarketGroup::withCount('markets')->orderBy('id')->get(),
            'otherGroups' => MarketGroup::query()->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('groups.form', ['marketGroup' => new MarketGroup, 'editing' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        MarketGroup::create($data);

        return redirect()->route('groups.index')->with('success', 'تمت إضافة المنطقة.');
    }

    public function edit(MarketGroup $marketGroup): View
    {
        return view('groups.form', compact('marketGroup') + ['editing' => true]);
    }

    public function update(Request $request, MarketGroup $marketGroup): RedirectResponse
    {
        $marketGroup->update($this->validatedData($request, $marketGroup));

        return redirect()->route('groups.index')->with('success', 'تم تحديث المنطقة.');
    }

    public function destroy(Request $request, MarketGroup $marketGroup): RedirectResponse
    {
        $targetGroupId = null;
        if ($marketGroup->markets()->exists()) {
            $data = $request->validate([
                'transfer_to_group' => ['required', 'integer', Rule::exists('market_groups', 'id')->whereNot('id', $marketGroup->id)],
            ]);
            $targetGroupId = (int) $data['transfer_to_group'];
        }

        DB::transaction(function () use ($marketGroup, $targetGroupId): void {
            $lockedGroup = MarketGroup::query()->lockForUpdate()->findOrFail($marketGroup->id);
            if ($targetGroupId !== null) {
                $lockedGroup->markets()->update(['market_group_id' => $targetGroupId]);
            }

            $lockedGroup->delete();
        });

        return redirect()->route('groups.index')->with('success', $targetGroupId ? 'تم نقل الأسواق وحذف المنطقة.' : 'تم حذف المنطقة.');
    }

    public function show(MarketGroup $marketGroup): View
    {
        $markets = $marketGroup->markets()->with(['group', 'sections', 'expansions', 'rentPeriods'])->latest()->paginate(9);

        return view('groups.show', compact('marketGroup', 'markets'));
    }

    private function validatedData(Request $request, ?MarketGroup $marketGroup = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('market_groups', 'slug')->ignore($marketGroup)],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
