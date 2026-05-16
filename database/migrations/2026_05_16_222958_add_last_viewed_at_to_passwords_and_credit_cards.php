<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passwords', function (Blueprint $table) {
            $table->timestamp('last_viewed_at')->nullable()->after('updated_at');
        });

        Schema::table('credit_cards', function (Blueprint $table) {
            $table->timestamp('last_viewed_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('passwords', function (Blueprint $table) {
            $table->dropColumn('last_viewed_at');
        });

        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropColumn('last_viewed_at');
        });
    }
};
