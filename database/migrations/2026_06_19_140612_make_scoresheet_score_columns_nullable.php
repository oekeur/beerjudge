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
        Schema::table('scoresheets', function (Blueprint $table) {
            $table->unsignedTinyInteger('aroma')->nullable()->default(null)->change();
            $table->unsignedTinyInteger('appearance')->nullable()->default(null)->change();
            $table->unsignedTinyInteger('flavour')->nullable()->default(null)->change();
            $table->unsignedTinyInteger('mouthfeel')->nullable()->default(null)->change();
            $table->unsignedTinyInteger('overall')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('scoresheets', function (Blueprint $table) {
            $table->unsignedTinyInteger('aroma')->nullable(false)->default(0)->change();
            $table->unsignedTinyInteger('appearance')->nullable(false)->default(0)->change();
            $table->unsignedTinyInteger('flavour')->nullable(false)->default(0)->change();
            $table->unsignedTinyInteger('mouthfeel')->nullable(false)->default(0)->change();
            $table->unsignedTinyInteger('overall')->nullable(false)->default(0)->change();
        });
    }
};
