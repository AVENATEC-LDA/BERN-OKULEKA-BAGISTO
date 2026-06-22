<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unitel_money_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_uid')->nullable()->index();
            $table->unsignedInteger('order_id')->nullable()->index();
            $table->unsignedInteger('cart_id')->nullable()->index();
            $table->string('originator_conversation_id')->nullable()->index();
            $table->string('conversation_id')->nullable()->index();
            $table->string('transaction_id')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unitel_money_logs');
    }
};
