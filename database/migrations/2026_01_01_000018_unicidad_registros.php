<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Evita registros duplicados manteniendo compatibilidad con el borrado
     * lógico: se agrega una columna generada `activo` (1 en filas vigentes y
     * NULL en las eliminadas) y la unicidad se aplica sobre las columnas
     * relevantes más `activo`. Al ser NULL, las filas eliminadas no bloquean
     * el registro de un nuevo valor.
     */
    public function up(): void
    {
        $this->agregarUnicidad('centros', ['descripcion'], 'centros_descripcion_activo_unique');
        $this->agregarUnicidad('bases', ['descripcion'], 'bases_descripcion_activo_unique');
        $this->agregarUnicidad('mesas', ['descripcion', 'centro_id'], 'mesas_descripcion_centro_activo_unique');
        $this->agregarUnicidad('personeros', ['persona_id'], 'personeros_persona_activo_unique');
        $this->agregarUnicidad('afiliados', ['persona_id'], 'afiliados_persona_activo_unique');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->quitarUnicidad('centros', 'centros_descripcion_activo_unique');
        $this->quitarUnicidad('bases', 'bases_descripcion_activo_unique');
        $this->quitarUnicidad('mesas', 'mesas_descripcion_centro_activo_unique');
        $this->quitarUnicidad('personeros', 'personeros_persona_activo_unique');
        $this->quitarUnicidad('afiliados', 'afiliados_persona_activo_unique');
    }

    /**
     * Agrega la columna generada `activo` y el índice único de vigentes.
     *
     * @param  list<string>  $columnas
     */
    private function agregarUnicidad(string $tabla, array $columnas, string $nombre): void
    {
        Schema::table($tabla, function (Blueprint $table) {
            $table->unsignedTinyInteger('activo')
                ->nullable()
                ->storedAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END')
                ->after('deleted_at');
        });

        Schema::table($tabla, function (Blueprint $table) use ($columnas, $nombre) {
            $table->unique(array_merge($columnas, ['activo']), $nombre);
        });
    }

    /**
     * Quita el índice único y la columna generada `activo`.
     */
    private function quitarUnicidad(string $tabla, string $nombre): void
    {
        Schema::table($tabla, function (Blueprint $table) use ($nombre) {
            $table->dropUnique($nombre);
        });

        Schema::table($tabla, function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};