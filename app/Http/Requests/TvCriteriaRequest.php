<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class TvCriteriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'first_air_date_gte' => 'Start Year',
            'first_air_date_lte' => 'End Year',
            'vote_average_lte'   => 'Highest Score',
            'vote_average_gte'   => 'Lowest Score',
            'vote_count_gte'     => 'Vote Count',
        ];
    }

    public function messages(): array
    {
        return [
            'vote_average_lte.gte' => 'Highest score must be higher or equal to the lowest score',
        ];
    }

    public function rules(): array
    {
        $now   = Carbon::now()->format('Y');
        $value = $this->vote_average_gte ?? 0;

        return [
            'first_air_date_gte' => 'numeric|max:' . $now . '|min:1900|nullable',
            'first_air_date_lte' => 'numeric|max:' . $now . '|min:1900|nullable|after_or_equal:first_air_date_gte',
            'vote_average_lte'   => 'numeric|nullable|min:0|max:10|gte:' . $value,
            'vote_average_gte'   => 'numeric|nullable|min:0|max:10',
            'vote_count_gte'     => 'numeric|nullable|min:0',
        ];
    }
}
