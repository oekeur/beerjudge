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
        Schema::table('styles', function (Blueprint $table) {
            $table->text('overall_impression')->nullable()->after('version');
            $table->text('aroma')->nullable()->after('overall_impression');
            $table->text('appearance')->nullable()->after('aroma');
            $table->text('flavor')->nullable()->after('appearance');
            $table->text('mouthfeel')->nullable()->after('flavor');
            $table->text('comments')->nullable()->after('mouthfeel');
            $table->text('history')->nullable()->after('comments');
            $table->text('style_comparison')->nullable()->after('history');
            $table->text('ingredients')->nullable()->after('style_comparison');
            $table->text('examples')->nullable()->after('ingredients');
            $table->string('tags')->nullable()->after('examples');
        });
    }

    public function down(): void
    {
        Schema::table('styles', function (Blueprint $table) {
            $table->dropColumn([
                'overall_impression', 'aroma', 'appearance', 'flavor', 'mouthfeel',
                'comments', 'history', 'style_comparison', 'ingredients', 'examples', 'tags',
            ]);
        });
    }
};
