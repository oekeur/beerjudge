<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('styles', function (Blueprint $table) {
            $table->decimal('og_min', 4, 3)->nullable()->change();
            $table->decimal('og_max', 4, 3)->nullable()->change();
            $table->decimal('fg_min', 4, 3)->nullable()->change();
            $table->decimal('fg_max', 4, 3)->nullable()->change();
            $table->decimal('abv_min', 4, 1)->nullable()->change();
            $table->decimal('abv_max', 4, 1)->nullable()->change();
            $table->decimal('ibu_min', 5, 1)->nullable()->change();
            $table->decimal('ibu_max', 5, 1)->nullable()->change();
            $table->decimal('srm_min', 5, 1)->nullable()->change();
            $table->decimal('srm_max', 5, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('styles', function (Blueprint $table) {
            $table->float('og_min')->nullable()->change();
            $table->float('og_max')->nullable()->change();
            $table->float('fg_min')->nullable()->change();
            $table->float('fg_max')->nullable()->change();
            $table->float('abv_min')->nullable()->change();
            $table->float('abv_max')->nullable()->change();
            $table->float('ibu_min')->nullable()->change();
            $table->float('ibu_max')->nullable()->change();
            $table->float('srm_min')->nullable()->change();
            $table->float('srm_max')->nullable()->change();
        });
    }
};
