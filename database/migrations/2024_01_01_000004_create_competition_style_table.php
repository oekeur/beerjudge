<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_style', function (Blueprint $table) {
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->primary(['competition_id', 'style_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_style');
    }
};
