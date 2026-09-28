<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Registro de votos por partido y persona (personero que reporta): la
     * columna `votos` guarda la cantidad actual de votos registrada.
     */
    public function up(): void
    {
        Schema::create('votos', function (Blueprint $table) {
            $table->id('voto_id');
            $table->unsignedBigInteger('partido_id');
            $table->unsignedInteger('persona_id');
            $table->integer('votos')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Registro de auditoría: quien creó y quien editó
            $table->unsignedBigInteger('autor_id')->nullable();
            $table->unsignedBigInteger('editor_id')->nullable();

            // Un partido tiene un único registro de votos por persona
            $table->unique(['partido_id', 'persona_id'], 'votos_partido_persona_unique');

            $table->foreign('partido_id')
                ->references('partido_id')
                ->on('partidos')
                ->restrictOnDelete();
            $table->foreign('persona_id')
                ->references('persona_id')
                ->on('personas')
                ->restrictOnDelete();
            $table->foreign('autor_id')
                ->references('usuario_id')
                ->on('usuarios')
                ->nullOnDelete();
            $table->foreign('editor_id')
                ->references('usuario_id')
                ->on('usuarios')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
