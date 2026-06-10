<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('style_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('bottle_number')->nullable();
            $table->text('brewer_notes')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'bottle_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
