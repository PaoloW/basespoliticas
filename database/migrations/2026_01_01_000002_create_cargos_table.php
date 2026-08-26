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
        Schema::create('cargos', function (Blueprint $table) {
            $table->id('cargo_id');
            $table->string('descripcion', 255);

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
        Schema::dropIfExists('cargos');
    }
};