<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('styles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('category_name');
            $table->string('name');
            $table->string('version', 10)->default('2021');
            $table->float('og_min')->nullable();
            $table->float('og_max')->nullable();
            $table->float('fg_min')->nullable();
            $table->float('fg_max')->nullable();
            $table->float('abv_min')->nullable();
            $table->float('abv_max')->nullable();
            $table->float('ibu_min')->nullable();
            $table->float('ibu_max')->nullable();
            $table->float('srm_min')->nullable();
            $table->float('srm_max')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('styles');
    }
};
