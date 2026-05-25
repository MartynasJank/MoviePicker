@php
    $countryId   = isset($modalMode) && $modalMode ? 'modal-with_origin_country' : 'with_origin_country';
    $selCountry  = $selectedCountry ?? '';
    $countryOpts = [
        'US'=>'United States','GB'=>'United Kingdom','FR'=>'France','DE'=>'Germany',
        'IT'=>'Italy','ES'=>'Spain','JP'=>'Japan','KR'=>'South Korea','CN'=>'China',
        'IN'=>'India','AU'=>'Australia','CA'=>'Canada','MX'=>'Mexico','BR'=>'Brazil',
        'DK'=>'Denmark','SE'=>'Sweden','NO'=>'Norway','NL'=>'Netherlands','PL'=>'Poland',
        'RU'=>'Russia','TR'=>'Turkey','TH'=>'Thailand','TW'=>'Taiwan','HK'=>'Hong Kong',
        'IE'=>'Ireland','IS'=>'Iceland','BE'=>'Belgium','PT'=>'Portugal','AR'=>'Argentina',
        'IR'=>'Iran','IL'=>'Israel','GR'=>'Greece','LT'=>'Lithuania',
    ];
@endphp
<select id="{{ $countryId }}" name="with_origin_country">
    <option value="">Any Country</option>
    @foreach($countryOpts as $val => $label)
        <option value="{{ $val }}" {{ $selCountry === $val ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
