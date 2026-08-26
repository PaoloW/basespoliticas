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
        Schema::create('mesas', function (Blueprint $table) {
            $table->id('mesa_id');
            $table->integer('descripcion')->nullable();
            $table->unsignedBigInteger('centro_id');
            $table->integer('votantes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Registro de auditoría: quien creó y quien editó
            $table->unsignedBigInteger('autor_id')->nullable();
            $table->unsignedBigInteger('editor_id')->nullable();

            $table->foreign('centro_id')
                ->references('centro_id')
                ->on('centros')
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
        Schema::dropIfExists('mesas');
    }
};