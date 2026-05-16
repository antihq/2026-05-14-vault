<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

#[Fillable(['team_id', 'name', 'name_on_card', 'card_number', 'expiry_date', 'cvv', 'notes', 'last_viewed_at'])]
class CreditCard extends Model
{
    protected function casts(): array
    {
        return [
            'last_viewed_at' => 'datetime',
        ];
    }

    /**
     * Get the team that owns the credit card.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get and set the card number attribute.
     *
     * Encrypts on write to the `encrypted_card_number` column.
     * Decrypts on read from the `encrypted_card_number` column.
     * Extracts `last_four` from the plaintext value on write.
     */
    protected function cardNumber(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['encrypted_card_number'])
                ? Crypt::decryptString($attributes['encrypted_card_number'])
                : null,
            set: fn (string $value) => [
                'encrypted_card_number' => Crypt::encryptString($value),
                'last_four' => substr(preg_replace('/\D/', '', $value), -4),
            ],
        );
    }

    /**
     * Get and set the CVV attribute.
     *
     * Encrypts on write to the `encrypted_cvv` column.
     * Decrypts on read from the `encrypted_cvv` column.
     */
    protected function cvv(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['encrypted_cvv'])
                ? Crypt::decryptString($attributes['encrypted_cvv'])
                : null,
            set: fn (string $value) => ['encrypted_cvv' => Crypt::encryptString($value)],
        );
    }

    /**
     * Get and set the notes attribute.
     *
     * Encrypts on write to the `encrypted_notes` column.
     * Decrypts on read from the `encrypted_notes` column.
     */
    protected function notes(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['encrypted_notes'])
                ? Crypt::decryptString($attributes['encrypted_notes'])
                : null,
            set: fn (?string $value) => ['encrypted_notes' => $value !== null ? Crypt::encryptString($value) : null],
        );
    }

    /**
     * Get the masked card number for display.
     *
     * Returns the last four digits prefixed with bullet characters,
     * e.g. "•••• •••• •••• 4242".
     */
    protected function maskedNumber(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => '•••• •••• •••• ' . ($attributes['last_four'] ?? '    '),
        );
    }

    /**
     * Determine if the credit card has expired.
     *
     * Parses the expiry_date (MM/YY) and compares the end of that month
     * to the current date and time.
     */
    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Carbon::createFromFormat('m/y', $attributes['expiry_date'])
                ->endOfMonth()
                ->endOfDay()
                ->isPast(),
        );
    }
}
