<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restructura `votos`: el conteo pertenece a un personero (ya no a una
     * persona) y se conserva el partido, quedando una fila por cada
     * combinación (personero, partido).
     */
    public function up(): void
    {
        // 1. Quita las claves foráneas (usan el índice compuesto que se eliminará).
        Schema::table('votos', function (Blueprint $table) {
            $table->dropForeign(['partido_id']);
            $table->dropForeign(['persona_id']);
        });

        // 2. Quita la restricción anterior (partido + persona) y la columna persona.
        Schema::table('votos', function (Blueprint $table) {
            $table->dropUnique('votos_partido_persona_unique');
        });

        Schema::table('votos', function (Blueprint $table) {
            $table->dropColumn('persona_id');
        });

        // 3. Agrega el personero dueño del conteo y las nuevas restricciones.
        Schema::table('votos', function (Blueprint $table) {
            $table->unsignedBigInteger('personero_id')->nullable()->after('voto_id');

            $table->foreign('partido_id')
                ->references('partido_id')
                ->on('partidos')
                ->restrictOnDelete();
            $table->foreign('personero_id')
                ->references('personero_id')
                ->on('personeros')
                ->restrictOnDelete();

            // Un conteo por personero y partido
            $table->unique(['personero_id', 'partido_id'], 'votos_personero_partido_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votos', function (Blueprint $table) {
            $table->dropForeign(['partido_id']);
            $table->dropForeign(['personero_id']);
        });

        Schema::table('votos', function (Blueprint $table) {
            $table->dropUnique('votos_personero_partido_unique');
        });

        Schema::table('votos', function (Blueprint $table) {
            $table->dropColumn('personero_id');
        });

        Schema::table('votos', function (Blueprint $table) {
            $table->unsignedInteger('persona_id')->nullable()->after('voto_id');

            $table->foreign('partido_id')
                ->references('partido_id')
                ->on('partidos')
                ->restrictOnDelete();
            $table->foreign('persona_id')
                ->references('persona_id')
                ->on('personas')
                ->restrictOnDelete();

            $table->unique(['partido_id', 'persona_id'], 'votos_partido_persona_unique');
        });
    }
};