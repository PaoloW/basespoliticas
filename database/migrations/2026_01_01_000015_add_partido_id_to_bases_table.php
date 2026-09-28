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
        Schema::table('bases', function (Blueprint $table) {
            $table->unsignedBigInteger('partido_id')->nullable()->after('base_id');

            $table->foreign('partido_id')
                ->references('partido_id')
                ->on('partidos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bases', function (Blueprint $table) {
            $table->dropForeign(['partido_id']);
            $table->dropColumn('partido_id');
        });
    }
};
