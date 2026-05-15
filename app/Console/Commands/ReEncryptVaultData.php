<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ReEncryptVaultData extends Command
{
    protected $signature = 'app:re-encrypt-vault-data';

    protected $description = 'Re-encrypt vault data to use encryptString (no serialization)';

    public function handle(): int
    {
        $tables = [
            'passwords' => ['encrypted_password', 'encrypted_notes'],
            'credit_cards' => ['encrypted_card_number', 'encrypted_cvv', 'encrypted_notes'],
        ];

        foreach ($tables as $table => $columns) {
            $this->info("Processing {$table}...");

            $records = DB::table($table)->get();
            $count = 0;

            foreach ($records as $record) {
                $updates = [];

                foreach ($columns as $column) {
                    $encrypted = $record->{$column} ?? null;

                    if ($encrypted === null) {
                        continue;
                    }

                    $plaintext = $this->decryptValue($encrypted);

                    if ($plaintext !== null) {
                        $updates[$column] = Crypt::encryptString($plaintext);
                    }
                }

                if (!empty($updates)) {
                    DB::table($table)->where('id', $record->id)->update($updates);
                    $count++;
                }
            }

            $this->info("  Re-encrypted {$count} {$table} records.");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function decryptValue(string $encrypted): ?string
    {
        try {
            return decrypt($encrypted);
        } catch (\Throwable) {
            try {
                return Crypt::decryptString($encrypted);
            } catch (\Throwable) {
                $this->warn('  Could not decrypt a value, skipping.');
                return null;
            }
        }
    }
}
