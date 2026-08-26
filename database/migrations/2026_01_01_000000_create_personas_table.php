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
        Schema::create('personas', function (Blueprint $table) {
            $table->unsignedInteger('persona_id')->primary();
            $table->string('dni', 255)->unique();
            $table->string('nombres', 255);
            $table->string('primer_apellido', 255);
            $table->string('segundo_apellido', 255);
            $table->string('codigo_mesa', 255)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Registro de auditoría: quien creó y quien editó
            $table->unsignedBigInteger('autor_id')->nullable();
            $table->unsignedBigInteger('editor_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};