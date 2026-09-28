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
        Schema::table('personeros', function (Blueprint $table) {
            $table->unsignedBigInteger('afiliado_id')->nullable()->after('personero_id');
        });

        // Vincula cada personero con la afiliación activa de su persona.
        DB::statement('UPDATE personeros p JOIN afiliados a ON a.persona_id = p.persona_id AND a.deleted_at IS NULL SET p.afiliado_id = a.afiliado_id WHERE p.afiliado_id IS NULL');

        try {
            Schema::table('personeros', function (Blueprint $table) {
                $table->dropUnique('personeros_persona_activo_unique');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('personeros', function (Blueprint $table) {
            $table->foreign('afiliado_id')->references('afiliado_id')->on('afiliados')->restrictOnDelete();
            $table->unique(['afiliado_id', 'activo'], 'personeros_afiliado_activo_unique');
        });
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
