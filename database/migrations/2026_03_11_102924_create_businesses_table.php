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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique(); // unique enforces 1:1
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['sole_proprietor', 'partnership', 'limited'])->nullable();
            $table->string('industry')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->date('established_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedTinyInteger('kreditsu_score')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
