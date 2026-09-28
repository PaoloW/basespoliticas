<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix 1: el segundo apellido (segundo nombre) de la persona deja de ser
     * obligatorio en la base de datos.
     * Fix 4: una mesa solo puede tener un personero (apoderado) vigente.
     * Fix 5: los partidos guardan la ruta de su logo.
     */
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->string('segundo_apellido', 255)->nullable()->change();
        });

        Schema::table('partidos', function (Blueprint $table) {
            $table->string('logo', 255)->nullable()->after('nombre');
        });

        Schema::table('personeros', function (Blueprint $table) {
            $table->unique(['mesa_id', 'activo'], 'personeros_mesa_activo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personeros', function (Blueprint $table) {
            $table->dropUnique('personeros_mesa_activo_unique');
        });

        Schema::table('partidos', function (Blueprint $table) {
            $table->dropColumn('logo');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->string('segundo_apellido', 255)->nullable(false)->change();
        });
    }
};
