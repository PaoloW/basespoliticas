<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Divide el acceso "Gestión de votos" en dos menús:
     *   - "Ver conteo de votos" (reporte de conteos registrados).
     *   - "Registrar conteo de votos" (formulario del personero).
     */
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Renombra el menú existente al de solo consulta.
            DB::table('menu')
                ->where('descripcion', 'Gestión de votos')
                ->update(['descripcion' => 'Ver conteo de votos', 'updated_at' => now()]);

            $verMenu = DB::table('menu')->where('descripcion', 'Ver conteo de votos')->first();

            if (! $verMenu) {
                return;
            }

            // 2. Crea el menú del formulario bajo el mismo padre (Procesos).
            $existe = DB::table('menu')
                ->where('descripcion', 'Registrar conteo de votos')
                ->where('menu_padre_id', $verMenu->menu_padre_id)
                ->exists();

            if (! $existe) {
                DB::table('menu')->insert([
                    'menu_padre_id' => $verMenu->menu_padre_id,
                    'descripcion' => 'Registrar conteo de votos',
                    'icono' => 'pi pi-pencil',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'autor_id' => null,
                    'editor_id' => null,
                ]);
            }

            $registrarId = DB::table('menu')
                ->where('descripcion', 'Registrar conteo de votos')
                ->where('menu_padre_id', $verMenu->menu_padre_id)
                ->value('menu_id');

            // 3. Quienes ya tenían acceso a votos conservan el del formulario.
            $usuarioIds = DB::table('accesos')
                ->where('menu_id', $verMenu->menu_id)
                ->whereNull('deleted_at')
                ->pluck('usuario_id')
                ->unique();

            foreach ($usuarioIds as $usuarioId) {
                DB::table('accesos')->insertOrIgnore([
                    'usuario_id' => $usuarioId,
                    'menu_id' => $registrarId,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'autor_id' => null,
                    'editor_id' => null,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            $registrarMenu = DB::table('menu')->where('descripcion', 'Registrar conteo de votos')->first();

            if ($registrarMenu) {
                DB::table('accesos')->where('menu_id', $registrarMenu->menu_id)->delete();
                DB::table('menu')->where('menu_id', $registrarMenu->menu_id)->delete();
            }

            DB::table('menu')
                ->where('descripcion', 'Ver conteo de votos')
                ->update(['descripcion' => 'Gestión de votos', 'updated_at' => now()]);
        });
    }
};