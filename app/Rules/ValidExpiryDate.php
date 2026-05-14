<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidExpiryDate implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', (string) $value)) {
            $fail('The expiry date must be in MM/YY format.');

            return;
        }

        [$month, $year] = explode('/', $value);

        $expiresAt = Carbon::createFromFormat('Y-m-d', "20{$year}-{$month}-01")
            ->endOfMonth()
            ->endOfDay();

        if ($expiresAt->isPast()) {
            $fail('The expiry date has passed.');
        }
    }
}
