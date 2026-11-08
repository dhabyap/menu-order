<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 80);
            $table->string('level', 10);
            $table->string('status', 10);
            $table->string('actor_type', 10)->nullable();
            $table->string('actor_id', 120)->nullable();
            $table->string('subject_type', 60)->nullable();
            $table->bigInteger('subject_id')->nullable();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->uuid('request_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['event', 'level']);
            $table->index('status');
            $table->index('request_id');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
