<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A checkout token lets the API return the same order when the customer
     * retries a request after a double click or a lost response.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('client_token', 64)->nullable()->unique()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['client_token']);
            $table->dropColumn('client_token');
        });
    }
};
