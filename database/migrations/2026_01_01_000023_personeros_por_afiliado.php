<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El personero se enlaza con el afiliado (no directo a la persona).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('personeros', 'afiliado_id')) {
            Schema::table('personeros', function (Blueprint $table) {
                $table->unsignedBigInteger('afiliado_id')->nullable()->after('personero_id');
            });
        }

        // Vincula cada personero con la afiliación activa de su persona (portable MySQL/SQLite).
        DB::statement('UPDATE personeros SET afiliado_id = (SELECT a.afiliado_id FROM afiliados a WHERE a.persona_id = personeros.persona_id AND a.deleted_at IS NULL LIMIT 1) WHERE afiliado_id IS NULL');

        try {
            Schema::table('personeros', function (Blueprint $table) {
                $table->dropUnique('personeros_persona_activo_unique');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('personeros', function (Blueprint $table) {
                $table->foreign('afiliado_id')->references('afiliado_id')->on('afiliados')->restrictOnDelete();
                $table->unique(['afiliado_id', 'activo'], 'personeros_afiliado_activo_unique');
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::table('personeros', function (Blueprint $table) {
            try {
                $table->dropUnique('personeros_afiliado_activo_unique');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropForeign(['afiliado_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('personeros', function (Blueprint $table) {
            $table->dropColumn('afiliado_id');
            $table->unique(['persona_id', 'activo'], 'personeros_persona_activo_unique');
        });
    }
};
