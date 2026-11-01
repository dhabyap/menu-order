<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_quote_source', 40)->nullable()->after('delivery_variable_fee');
            $table->boolean('delivery_is_estimate')->default(false)->after('delivery_quote_source');
            $table->decimal('delivery_lat', 10, 7)->nullable()->after('delivery_is_estimate');
            $table->decimal('delivery_lon', 10, 7)->nullable()->after('delivery_lat');
            $table->timestamp('delivery_confirmed_at')->nullable()->after('delivery_lon');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_quote_source',
                'delivery_is_estimate',
                'delivery_lat',
                'delivery_lon',
                'delivery_confirmed_at',
            ]);
        });
    }
};
