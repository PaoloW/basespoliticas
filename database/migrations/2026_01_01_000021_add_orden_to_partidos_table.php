<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columna de orden en partidos (sin duplicados) para mostrarlos
     * en ese orden en el registro de votos.
     */
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->unsignedInteger('orden')->nullable()->after('logo');
        });

        // Asigna orden correlativo a los partidos existentes (por nombre).
        $orden = 0;
        foreach (\App\Models\Partido::orderBy('nombre')->get() as $partido) {
            $orden++;
            $partido->orden = $orden;
            $partido->saveQuietly();
        }

        Schema::table('partidos', function (Blueprint $table) {
            $table->unique('orden', 'partidos_orden_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropUnique('partidos_orden_unique');
            $table->dropColumn('orden');
        });
    }
};
