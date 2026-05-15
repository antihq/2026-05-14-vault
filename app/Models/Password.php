<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

#[Fillable(['team_id', 'name', 'username', 'password', 'website', 'notes'])]
class Password extends Model
{
    /**
     * Get the team that owns the password.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get and set the password attribute.
     *
     * Encrypts on write to the `encrypted_password` column.
     * Decrypts on read from the `encrypted_password` column.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['encrypted_password'])
                ? Crypt::decryptString($attributes['encrypted_password'])
                : null,
            set: fn (string $value) => ['encrypted_password' => Crypt::encryptString($value)],
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
}
