<label class="field"><span>{{ $label }}</span><div class="input-icon"><i data-lucide="calendar-days"></i><select id="{{ $pickerId }}" name="{{ $name }}" @required($required ?? false)>
@if(!($required ?? false))<option value="">مستمرة</option>@endif
@for($year = $min; $year <= $max; $year++)<option value="{{ $year }}" @selected((string) $value === (string) $year)>{{ $year }} / {{ $year + 1 }}</option>@endfor
</select></div></label>
