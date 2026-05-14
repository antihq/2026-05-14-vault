<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Luhn implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $number = preg_replace('/\D/', '', (string) $value);

        if (strlen($number) < 13 || strlen($number) > 19) {
            $fail('The card number is not valid.');

            return;
        }

        $sum = 0;
        $length = strlen($number);
        $parity = $length % 2;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        if ($sum % 10 !== 0) {
            $fail('The card number is not valid.');
        }
    }
}
