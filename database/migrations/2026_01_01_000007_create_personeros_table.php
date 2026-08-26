<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personeros', function (Blueprint $table) {
            $table->id('personero_id');
            $table->unsignedInteger('persona_id');
            $table->unsignedBigInteger('mesa_id');
            $table->integer('conteo')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Registro de auditoría: quien creó y quien editó
            $table->unsignedBigInteger('autor_id')->nullable();
            $table->unsignedBigInteger('editor_id')->nullable();

            $table->foreign('persona_id')
                ->references('persona_id')
                ->on('personas')
                ->restrictOnDelete();
            $table->foreign('mesa_id')
                ->references('mesa_id')
                ->on('mesas')
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
        Schema::dropIfExists('personeros');
    }
};