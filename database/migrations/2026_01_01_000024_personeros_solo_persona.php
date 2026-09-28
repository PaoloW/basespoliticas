<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personero y Afiliado trabajan solo con la persona: se elimina el enlace
     * directo del personero con la afiliación (personeros.afiliado_id) y la
     * unicidad vigente pasa a ser por persona (un personero vigente por persona).
     */
    public function up(): void
    {
        // 1. Deja una sola fila vigente por persona: antes se podía duplicar la
        //    persona cuando el personero no tenía afiliación (afiliado_id nulo).
        $this->quitarDuplicadosVigentes();

        // 2. Quita la clave foránea y luego el índice único por afiliación.
        try {
            Schema::table('personeros', function (Blueprint $table) {
                $table->dropForeign(['afiliado_id']);
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('personeros', function (Blueprint $table) {
                $table->dropUnique('personeros_afiliado_activo_unique');
            });
        } catch (\Throwable $e) {
        }

        // 3. Elimina la columna si existe: la relación del personero es solo con la persona.
        if (Schema::hasColumn('personeros', 'afiliado_id')) {
            Schema::table('personeros', function (Blueprint $table) {
                $table->dropColumn('afiliado_id');
            });
        }

        // 4. Un personero vigente por persona.
        try {
            Schema::table('personeros', function (Blueprint $table) {
                $table->unique(['persona_id', 'activo'], 'personeros_persona_activo_unique');
            });
        } catch (\Throwable $e) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personeros', function (Blueprint $table) {
            try {
                $table->dropUnique('personeros_persona_activo_unique');
            } catch (\Throwable $e) {
            }
        });

        Schema::table('personeros', function (Blueprint $table) {
            $table->unsignedBigInteger('afiliado_id')->nullable()->after('personero_id');
        });

        // Reconstruye el enlace con la afiliación vigente de cada persona.
        DB::statement('UPDATE personeros SET afiliado_id = (SELECT a.afiliado_id FROM afiliados a WHERE a.persona_id = personeros.persona_id AND a.deleted_at IS NULL LIMIT 1) WHERE afiliado_id IS NULL');

        Schema::table('personeros', function (Blueprint $table) {
            $table->foreign('afiliado_id')->references('afiliado_id')->on('afiliados')->restrictOnDelete();
            $table->unique(['afiliado_id', 'activo'], 'personeros_afiliado_activo_unique');
        });
    }

    /**
     * Deja vigente (deleted_at nulo) solo el personero de menor id por persona.
     */
    private function quitarDuplicadosVigentes(): void
    {
        $personas = DB::table('personeros')
            ->whereNull('deleted_at')
            ->select('persona_id')
            ->groupBy('persona_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('persona_id');

        foreach ($personas as $personaId) {
            $sobrantes = DB::table('personeros')
                ->whereNull('deleted_at')
                ->where('persona_id', $personaId)
                ->orderBy('personero_id')
                ->pluck('personero_id')
                ->slice(1)
                ->all();

            if ($sobrantes !== []) {
                DB::table('personeros')->whereIn('personero_id', $sobrantes)->update(['deleted_at' => now()]);
            }
        }
    }
};
