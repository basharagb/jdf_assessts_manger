<?php

namespace App\Http\Controllers;

use App\AnnualLease;
use App\Models\Market;
use App\Models\MarketGroup;
use App\Models\MarketSection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', [
            'groups' => MarketGroup::withCount('markets')->orderBy('id')->get(),
            'markets' => Market::with(['group', 'sections'])->orderBy('name')->get(),
        ]);
    }

    public function market(Request $request, Market $market): View|Response
    {
        return $this->report($request, 'تقرير '.$market->name, collect([$market->load(['group', 'sections', 'expansions', 'rentPeriods'])]), 'market-'.$market->id, true);
    }

    /** A report for one section of a split market (e.g. arrivals only), built by narrowing the market to that section. */
    public function marketSection(Request $request, Market $market, MarketSection $section): View|Response
    {
        $market->load(['group', 'sections', 'expansions', 'rentPeriods']);
        $title = 'تقرير '.$market->nameWithoutSections().' — '.$section->name;
        $market->setRelation('sections', $market->sections->where('id', $section->id)->values());

        return $this->report($request, $title, collect([$market]), 'market-'.$market->id.'-section-'.$section->id, true, $section);
    }

    public function group(Request $request, MarketGroup $marketGroup): View|Response
    {
        return $this->report($request, 'تقرير '.$marketGroup->name, $marketGroup->markets()->with(['group', 'sections', 'expansions', 'rentPeriods'])->orderBy('name')->get(), 'group-'.$marketGroup->slug);
    }

    public function all(Request $request): View|Response
    {
        return $this->report($request, 'التقرير الشامل لجميع الأسواق والمناطق', Market::with(['group', 'sections', 'expansions', 'rentPeriods'])->orderBy('market_group_id')->orderBy('name')->get(), 'all-markets');
    }

    /** @param Collection<int, Market> $markets */
    private function report(Request $request, string $title, Collection $markets, string $filename, bool $isSingleMarket = false, ?MarketSection $reportSection = null): View|Response
    {
        $request->validate(['format' => ['nullable', 'in:pdf']]);
        $entries = $markets->map(function (Market $market) use ($request): array {
            $lastYear = $market->lease_end_date ? AnnualLease::lastYear($market) : AnnualLease::currentYear($market);
            $rows = AnnualLease::ledger($market, $market->lease_start_date->year, $lastYear);

            return [
                'market' => $market, 'rows' => $rows, 'totals' => AnnualLease::totals($rows), 'currentRents' => $market->displayedRentBySection(),
                'currentRent' => round(array_sum($market->displayedRentBySection()), 2),
                'averageIncrease' => AnnualLease::averageIncreaseRate(AnnualLease::increaseRates($this->yearlyTotals($rows), 'amount')),
                'image' => $request->query('format') === 'pdf' ? $this->pdfImage($market->image_path ?: 'markets/demo/jdf-generic-shop.jpg') : $this->image($market->image_path ?: 'markets/demo/jdf-generic-shop.jpg'),
            ];
        });
        $summary = [
            'count' => $markets->count(),
            'active' => $markets->where('is_active', true)->count(),
            'annual' => $entries->sum(fn (array $entry): float => $entry['currentRent']),
            'total' => $entries->sum(fn (array $entry): float => $entry['totals']['total']),
            'paid' => $entries->sum(fn (array $entry): float => $entry['totals']['paid']),
            'unpaid' => $entries->sum(fn (array $entry): float => $entry['totals']['unpaid']),
        ];
        $groupSummaries = $entries->groupBy(fn (array $entry): int => $entry['market']->market_group_id)->map(fn (Collection $group): array => [
            'name' => $group->first()['market']->group->name,
            'count' => $group->count(),
            'area' => $group->sum(fn (array $entry): float => (float) $entry['market']->area),
            'annual' => $group->sum(fn (array $entry): float => $entry['currentRent']),
            'total' => $group->sum(fn (array $entry): float => $entry['totals']['total']),
            'paid' => $group->sum(fn (array $entry): float => $entry['totals']['paid']),
            'unpaid' => $group->sum(fn (array $entry): float => $entry['totals']['unpaid']),
        ]);
        $pdf = $request->query('format') === 'pdf';
        $logo = $this->image(null, public_path('images/jdf_logo.png'));
        $data = compact('title', 'entries', 'summary', 'groupSummaries', 'pdf', 'logo', 'isSingleMarket', 'reportSection');
        if (! $pdf) {
            return view('reports.show', $data);
        }
        $temp = storage_path('app/mpdf-temp');
        File::ensureDirectoryExists($temp);
        $fontDirs = (new ConfigVariables)->getDefaults()['fontDir'];
        $fontData = (new FontVariables)->getDefaults()['fontdata'];
        $mpdf = new Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10,
            'margin_top' => 12, 'margin_bottom' => 16, 'tempDir' => $temp,
            'use_kwt' => true, 'default_font' => 'cairo', 'fontDir' => [...$fontDirs, public_path('fonts')],
            'fontdata' => $fontData + ['cairo' => ['R' => 'Cairo-Regular.ttf', 'B' => 'Cairo-Bold.ttf', 'useOTL' => 0xFF, 'useKashida' => 75]],
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor('الشؤون الادارية- توثيق وترخيص ممتلكات');
        $mpdf->SetHTMLFooter('<div style="text-align:center;font-size:9px;color:#64748b">الأسواق الحرة الأردنية | <span dir="ltr">{PAGENO} / {nbpg}</span></div>');
        $mpdf->WriteHTML(view('reports.show', $data)->render());

        return response($mpdf->Output('', Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'-'.now()->format('Y-m-d').'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * One row per rental year with the amounts of all the market's sections added up, for whole-market increase rates.
     *
     * @param  list<array{year: int, amount: float, base: float, expansion: float, status: string}>  $rows
     * @return list<array{amount: float, base: float, expansion: float, status: string}>
     */
    private function yearlyTotals(array $rows): array
    {
        return collect($rows)->groupBy('year')->map(fn (Collection $yearRows): array => [
            'amount' => round($yearRows->sum('amount'), 2),
            'base' => round($yearRows->sum('base'), 2),
            'expansion' => round($yearRows->sum('expansion'), 2),
            'status' => $yearRows->every(fn (array $row): bool => $row['status'] === 'vacant') ? 'vacant' : 'paid',
        ])->values()->all();
    }

    private function pdfImage(string $storagePath): ?string
    {
        $path = Storage::disk('public')->path($storagePath);
        if (! is_file($path)) {
            return null;
        }
        $info = getimagesize($path);
        if ($info === false || ! in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }
        $image = match ($info['mime']) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
        };
        $width = min(1400, imagesx($image));
        $height = (int) round(imagesy($image) * $width / imagesx($image));
        $resized = imagecreatetruecolor($width, $height);
        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
        $directory = storage_path('app/mpdf-images');
        File::ensureDirectoryExists($directory);
        $target = $directory.'/'.hash('sha256', $storagePath.'|'.filemtime($path).'|'.filesize($path)).'.jpg';
        if (! is_file($target)) {
            imagejpeg($resized, $target, 85);
        }
        imagedestroy($resized);
        imagedestroy($image);

        return $target;
    }

    private function image(?string $storagePath, ?string $localPath = null): ?string
    {
        $path = $localPath ?? Storage::disk('public')->path($storagePath);
        if (! is_file($path)) {
            return null;
        }
        $mime = mime_content_type($path);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }
}
