<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('styles', function (Blueprint $table) {
            $table->string('source', 50)->default('bjcp_beer')->after('id');
            $table->string('version', 10)->nullable()->default(null)->change();
            $table->dropUnique('styles_code_unique');
            $table->unique(['code', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('styles', function (Blueprint $table) {
            $table->dropUnique(['code', 'source']);
            $table->unique('code');
            $table->string('version', 10)->nullable(false)->default('2021')->change();
            $table->dropColumn('source');
        });
    }
};
