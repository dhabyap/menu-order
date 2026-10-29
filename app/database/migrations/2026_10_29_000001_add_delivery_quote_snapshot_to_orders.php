<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('delivery_distance_meters')->nullable();
            $table->unsignedInteger('delivery_base_fee')->nullable();
            $table->unsignedInteger('delivery_variable_fee')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_distance_meters', 'delivery_base_fee', 'delivery_variable_fee']);
        });
    }
};
