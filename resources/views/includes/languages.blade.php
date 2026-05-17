@php
    $langId   = isset($modalMode) && $modalMode ? 'modal-with_original_language' : 'with_original_language';
    $selLang  = $selectedLang ?? old('with_original_language', 'en');
    $langOpts = [
        'en'=>'English','de'=>'German','fr'=>'French','es'=>'Spanish','ja'=>'Japanese',
        'pt'=>'Portuguese','it'=>'Italian','ru'=>'Russian','zh'=>'Chinese','ko'=>'Korean',
        'nl'=>'Dutch','sv'=>'Swedish','hi'=>'Hindi','cs'=>'Czech','tr'=>'Turkish',
        'cn'=>'Cantonese','da'=>'Danish','ta'=>'Tamil','pl'=>'Polish','ml'=>'Malayalam',
        'ar'=>'Arabic','el'=>'Greek','id'=>'Indonesian','hu'=>'Hungarian','tl'=>'Tagalog',
        'fi'=>'Finnish','fa'=>'Persian','no'=>'Norwegian','xx'=>'No Language','te'=>'Telugu',
        'sr'=>'Serbian','th'=>'Thai','ro'=>'Romanian','he'=>'Hebrew','hr'=>'Croatian',
        'bn'=>'Bengali','vi'=>'Vietnamese','ms'=>'Malay','sl'=>'Slovenian','bg'=>'Bulgarian',
        'kn'=>'Kannada','sk'=>'Slovak','et'=>'Estonian','uk'=>'Ukrainian','lv'=>'Latvian',
        'sq'=>'Albanian','lt'=>'Lithuanian','ur'=>'Urdu','ka'=>'Georgian','pa'=>'Punjabi',
        'mr'=>'Marathi','ca'=>'Catalan','az'=>'Azerbaijani','bs'=>'Bosnian','is'=>'Icelandic',
        'mk'=>'Macedonian','af'=>'Afrikaans','eu'=>'Basque','hy'=>'Armenian','si'=>'Sinhalese',
        'ne'=>'Nepali','gl'=>'Galician','gu'=>'Gujarati','ku'=>'Kurdish','kk'=>'Kazakh',
        'mn'=>'Mongolian','zu'=>'Zulu','am'=>'Amharic','sw'=>'Swahili','my'=>'Burmese',
        'km'=>'Khmer','uz'=>'Uzbek','be'=>'Belarusian','la'=>'Latin','mt'=>'Maltese',
        'ga'=>'Irish','cy'=>'Welsh','eo'=>'Esperanto','yi'=>'Yiddish','so'=>'Somali',
        'sa'=>'Sanskrit','tt'=>'Tatar','yo'=>'Yoruba',
    ];
@endphp
<select id="{{ $langId }}" name="with_original_language">
    <option value="">All Languages</option>
    @foreach($langOpts as $val => $label)
        <option value="{{ $val }}" {{ $selLang === $val ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
<p class="text-xs text-gray-600 mt-1">Default: English</p>
