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
        Schema::create('accesos', function (Blueprint $table) {
            $table->id('acceso_id');
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('menu_id');

            $table->timestamps();
            $table->softDeletes();

            // Registro de auditoría: quien creó y quien editó
            $table->unsignedBigInteger('autor_id')->nullable();
            $table->unsignedBigInteger('editor_id')->nullable();

            // Evita que un mismo usuario tenga el mismo menú asignado dos veces
            $table->unique(['usuario_id', 'menu_id']);

            $table->foreign('usuario_id')
                ->references('usuario_id')
                ->on('usuarios')
                ->restrictOnDelete();
            $table->foreign('menu_id')
                ->references('menu_id')
                ->on('menu')
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
        Schema::dropIfExists('accesos');
    }
};
