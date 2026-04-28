<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class CheckFormData extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function attributes(){
        return[
            'primary_release_date_gte' => 'Start Year',
            'primary_release_date_lte' => 'End Year',
            'vote_average_lte' => 'Highest Score',
            'vote_average_gte' => 'Lowest Score',
            'vote_count_gte' => 'Vote Count',
        ];
    }

    public function messages()
    {
        return [
            'vote_average_lte.gte' => 'Highest score must be higher or equal to the lowest score',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Get current year
        $now = Carbon::now()->format('Y');

        // assisn value to lowest score if it's not assigned
        if($this->vote_average_gte == null){
            $value = 0;
        } else {
            $value = $this->vote_average_gte;
        }
        // $this->redirect = url()->previous().'#step-5';

        return [
            'primary_release_date_gte' => 'numeric|max:'.$now.'|min:1874|nullable',
            'primary_release_date_lte' => 'numeric|max:'.$now.'|min:1874|nullable|after_or_equal:primary_release_date_gte',
            'vote_average_lte' => 'numeric|nullable|min:0|max:10|gte:'.$value,
            'vote_average_gte' => 'numeric|nullable|min:0|max:10',
            'vote_count_gte' => 'numeric|nullable|min:0',
        ];
    }
}
