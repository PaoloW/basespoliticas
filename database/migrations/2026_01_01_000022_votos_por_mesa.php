<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El conteo pertenece a la mesa (ya no al personero): una fila por
     * cada combinación (mesa, partido). El personero sigue enlazado a la
     * mesa como apoderado de la misma.
     */
    public function up(): void
    {
        // 1. Agrega mesa_id nullable para el backfill.
        Schema::table('votos', function (Blueprint $table) {
            $table->unsignedBigInteger('mesa_id')->nullable()->after('voto_id');
        });

        // 2. Backfill: mesa del personero dueño del conteo (subconsulta portable MySQL/SQLite).
        DB::statement('UPDATE votos SET mesa_id = (SELECT p.mesa_id FROM personeros p WHERE p.personero_id = votos.personero_id LIMIT 1) WHERE mesa_id IS NULL');

        // 3. Quita FK y unicidad anteriores (nombres tolerantes a fallos).
        foreach (['votos_personero_id_foreign', 'votos_personero_partido_unique'] as $indice) {
            try {
                Schema::table('votos', function (Blueprint $table) use ($indice) {
                    if (str_ends_with($indice, '_foreign')) {
                        $table->dropForeign(['personero_id']);
                    } else {
                        $table->dropUnique($indice);
                    }
                });
            } catch (\Throwable $e) {
            }
        }

        // 4. Columna personero y restricciones nuevas.
        Schema::table('votos', function (Blueprint $table) {
            $table->dropColumn('personero_id');
        });

        Schema::table('votos', function (Blueprint $table) {
            $table->unsignedBigInteger('mesa_id')->nullable(false)->change();
            $table->foreign('mesa_id')->references('mesa_id')->on('mesas')->restrictOnDelete();
            $table->unique(['mesa_id', 'partido_id'], 'votos_mesa_partido_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votos', function (Blueprint $table) {
            try {
                $table->dropForeign(['mesa_id']);
            } catch (\Throwable $e) {
            }
            try {
                $table->dropUnique('votos_mesa_partido_unique');
            } catch (\Throwable $e) {
            }
        });

        Schema::table('votos', function (Blueprint $table) {
            $table->unsignedBigInteger('personero_id')->nullable()->after('voto_id');
        });

        DB::statement('UPDATE votos SET personero_id = (SELECT p.personero_id FROM personeros p WHERE p.mesa_id = votos.mesa_id LIMIT 1) WHERE personero_id IS NULL');

        Schema::table('votos', function (Blueprint $table) {
            $table->dropColumn('mesa_id');
        });

        Schema::table('votos', function (Blueprint $table) {
            $table->foreign('personero_id')->references('personero_id')->on('personeros')->restrictOnDelete();
            $table->unique(['personero_id', 'partido_id'], 'votos_personero_partido_unique');
        });
    }
};
