<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columna de color en partidos (hex #rrggbb) para identificarlos
     * visualmente en el registro de votos.
     */
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('orden');
        });

        // Rellena los partidos existentes con un color aleatorio.
        foreach (\App\Models\Partido::withTrashed()->get() as $partido) {
            $partido->color = '#'.str_pad(dechex(random_int(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
            $partido->saveQuietly();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};