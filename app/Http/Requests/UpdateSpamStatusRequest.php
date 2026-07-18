<?php

namespace App\Http\Requests;

use App\Services\Spam\EnquirySpamScorer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpamStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'spam_status' => ['required', Rule::in([
                EnquirySpamScorer::STATUS_CONFIRMED,
                EnquirySpamScorer::STATUS_NOT_SPAM,
                EnquirySpamScorer::STATUS_CLEAN,
                EnquirySpamScorer::STATUS_SUSPECTED,
            ])],
        ];
    }
}
