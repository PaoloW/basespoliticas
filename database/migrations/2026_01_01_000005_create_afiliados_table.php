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
        Schema::create('afiliados', function (Blueprint $table) {
            $table->id('afiliado_id');
            $table->unsignedInteger('persona_id');
            $table->unsignedBigInteger('base_id');
            $table->unsignedBigInteger('cargo_id');

            $table->timestamps();
            $table->softDeletes();

            // Registro de auditoría: quien creó y quien editó
            $table->unsignedBigInteger('autor_id')->nullable();
            $table->unsignedBigInteger('editor_id')->nullable();

            $table->foreign('persona_id')
                ->references('persona_id')
                ->on('personas')
                ->restrictOnDelete();
            $table->foreign('base_id')
                ->references('base_id')
                ->on('bases')
                ->restrictOnDelete();
            $table->foreign('cargo_id')
                ->references('cargo_id')
                ->on('cargos')
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
        Schema::dropIfExists('afiliados');
    }
};