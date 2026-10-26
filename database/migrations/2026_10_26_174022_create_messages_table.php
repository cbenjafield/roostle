<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbox_id')->constrained()->cascadeOnDelete();
            $table->string('message_id')->nullable();
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->json('to');
            $table->json('cc');
            $table->string('subject')->nullable();
            $table->longText('text_body')->nullable();
            $table->longText('html_body')->nullable();
            $table->json('headers');
            $table->string('raw_path');
            $table->unsignedBigInteger('size');
            $table->timestamp('received_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['inbox_id', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
