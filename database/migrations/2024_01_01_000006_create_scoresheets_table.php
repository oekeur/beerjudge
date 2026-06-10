<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scoresheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('members')->restrictOnDelete();
            $table->unsignedTinyInteger('aroma')->default(0);
            $table->unsignedTinyInteger('appearance')->default(0);
            $table->unsignedTinyInteger('flavour')->default(0);
            $table->unsignedTinyInteger('mouthfeel')->default(0);
            $table->unsignedTinyInteger('overall')->default(0);
            $table->unsignedTinyInteger('total')->default(0);
            $table->text('aroma_notes')->nullable();
            $table->text('appearance_notes')->nullable();
            $table->text('flavour_notes')->nullable();
            $table->text('mouthfeel_notes')->nullable();
            $table->text('overall_notes')->nullable();
            $table->char('client_uuid', 36)->unique();
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['entry_id', 'judge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoresheets');
    }
};
