@php($id = $pickerId)
@php($monthNames = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'])
@php($monthParts = $value ? explode('-', $value) : [])
@php($minYear = (int) substr($min, 0, 4))
@php($maxYear = (int) substr($max, 0, 4))
@php($pickerYear = (int) ($monthParts[0] ?? $minYear))
<div class="field month-picker" data-month-picker data-min="{{ $min }}" data-max="{{ $max }}">
    <span>{{ $label }}</span>
    <input id="{{ $id }}" type="hidden" name="{{ $name }}" value="{{ $value }}" @required($required)>
    <button class="month-picker-toggle" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="{{ $label }}">
        <i data-lucide="calendar-days"></i>
        <span data-month-display>{{ $monthParts ? $monthNames[(int) $monthParts[1]].' '.$monthParts[0] : 'اختر الشهر' }}</span>
        <i data-lucide="chevron-down"></i>
    </button>
    <div class="month-picker-popover" role="dialog" aria-label="اختيار الشهر والسنة" hidden>
        <div class="month-picker-year">
            <button type="button" data-year-step="-1" aria-label="السنة السابقة">‹</button>
            <label for="{{ $id }}Year">السنة</label>
            <select id="{{ $id }}Year" data-year-select aria-label="السنة">
                @for($year = $minYear; $year <= $maxYear; $year++)
                    <option value="{{ $year }}" @selected($pickerYear === $year)>{{ $year }}</option>
                @endfor
            </select>
            <button type="button" data-year-step="1" aria-label="السنة التالية">›</button>
        </div>
        <div class="month-picker-months">
            @foreach($monthNames as $number => $monthName)
                <button type="button" data-month="{{ sprintf('%02d', $number) }}">{{ $monthName }}</button>
            @endforeach
        </div>
        @unless($required)
            <button class="month-picker-clear" type="button" data-clear-month>مسح الشهر</button>
        @endunless
    </div>
</div>
