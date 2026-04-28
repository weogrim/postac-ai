@props([
    'name' => 'birthdate',
    'label' => 'Data urodzenia',
    'value' => null,
    'required' => false,
    'hint' => null,
])

@php
    $hasError = $errors->has($name);
    $current = old($name, $value);
    [$year, $month, $day] = ['', '', ''];
    if (is_string($current) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $current, $m)) {
        [$year, $month, $day] = [$m[1], $m[2], $m[3]];
    }
    $months = [
        '01' => 'styczeń', '02' => 'luty', '03' => 'marzec', '04' => 'kwiecień',
        '05' => 'maj', '06' => 'czerwiec', '07' => 'lipiec', '08' => 'sierpień',
        '09' => 'wrzesień', '10' => 'październik', '11' => 'listopad', '12' => 'grudzień',
    ];
    $currentYear = (int) date('Y');
    $minYear = $currentYear - 100;
    $maxYear = $currentYear - 13;
    $groupId = 'bd-'.$name;
@endphp

<fieldset class="fieldset" data-birthdate-picker="{{ $groupId }}">
    <label class="fieldset-legend">{{ $label }}</label>
    <div class="flex gap-2">
        <select
            data-birthdate-part="day"
            class="select w-full {{ $hasError ? 'select-error' : '' }}"
            @if ($required) required @endif
            aria-label="Dzień"
        >
            <option value="">dzień</option>
            @for ($d = 1; $d <= 31; $d++)
                @php $dd = str_pad((string) $d, 2, '0', STR_PAD_LEFT); @endphp
                <option value="{{ $dd }}" @selected($day === $dd)>{{ $d }}</option>
            @endfor
        </select>
        <select
            data-birthdate-part="month"
            class="select w-full {{ $hasError ? 'select-error' : '' }}"
            @if ($required) required @endif
            aria-label="Miesiąc"
        >
            <option value="">miesiąc</option>
            @foreach ($months as $mNum => $mName)
                <option value="{{ $mNum }}" @selected($month === $mNum)>{{ $mName }}</option>
            @endforeach
        </select>
        <select
            data-birthdate-part="year"
            class="select w-full {{ $hasError ? 'select-error' : '' }}"
            @if ($required) required @endif
            aria-label="Rok"
        >
            <option value="">rok</option>
            @for ($y = $maxYear; $y >= $minYear; $y--)
                <option value="{{ $y }}" @selected($year === (string) $y)>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <input type="hidden" name="{{ $name }}" data-birthdate-target value="{{ $current }}" />
    @if ($hint && ! $hasError)
        <p class="fieldset-label">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="fieldset-label text-error">{{ $message }}</p>
    @enderror
</fieldset>
