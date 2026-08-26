<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\MenuSeeder;

return new class extends Migration
{
    /**
     * DNI de la persona genérica de administración del sistema.
     * Usamos un valor que no colisione con los DNI reales (8 dígitos).
     */
    public const DNI_ADMIN = '00000000';

    /**
     * Clave por defecto con la que se crea el usuario administrador.
     * Se recomienda cambiarla tras la primera sesión.
     */
    public const CLAVE_ADMIN = 'admin123';

    /**
     * Run the migrations.
     *
     * Esta migración se ejecuta DESPUÉS de crear las tablas de `menu` (000009)
     * y `accesos` (000010), por lo que puede:
     *   1. Crear la persona y el usuario administrador.
     *   2. Seedear primero los menús (idempotente).
     *   3. Asignar TODOS los menús existentes al usuario administrador.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // === 1. Crear la persona genérica (si aún no existe) ===
            $persona = DB::table('personas')->where('dni', self::DNI_ADMIN)->first();

            if (! $persona) {
                DB::table('personas')->insert([
                    'persona_id' => 1, // columna no autoincremental, se define el id
                    'dni' => self::DNI_ADMIN,
                    'nombres' => 'Administrador',
                    'primer_apellido' => 'Sistema',
                    'segundo_apellido' => 'Genérico',
                    'codigo_mesa' => null,
                    'fecha_nacimiento' => null,
                    'telefono' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'autor_id' => null,
                    'editor_id' => null,
                ]);

                $persona = DB::table('personas')->where('dni', self::DNI_ADMIN)->first();
            }

            // === 2. Crear el usuario administrador para dicha persona (si aún no existe) ===
            $usuario = DB::table('usuarios')
                ->where('persona_id', $persona->persona_id)
                ->first();

            if (! $usuario) {
                DB::table('usuarios')->insert([
                    'persona_id' => $persona->persona_id,
                    'clave' => Hash::make(self::CLAVE_ADMIN),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'autor_id' => null,
                    'editor_id' => null,
                ]);

                $usuario = DB::table('usuarios')
                    ->where('persona_id', $persona->persona_id)
                    ->first();
            }

            // === 3. Primero se seedean los menús (idempotente) ===
            // Reutilizamos el MenuSeeder para garantizar que todos los menús
            // existan antes de asignarlos al administrador.
            (new MenuSeeder())->run();

            // === 4. Asignar TODOS los menús existentes al usuario admin ===
            // `insertOrIgnore` aprovecha el índice único (usuario_id, menu_id)
            // para ser idempotente: no duplica accesos si el seed se repite.
            $menuIds = DB::table('menu')->pluck('menu_id');

            foreach ($menuIds as $menuId) {
                DB::table('accesos')->insertOrIgnore([
                    'usuario_id' => $usuario->usuario_id,
                    'menu_id' => $menuId,
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
            // Eliminar los accesos a menús del usuario administrador
            DB::table('accesos')
                ->whereIn('usuario_id', function ($query) {
                    $query->select('usuarios.usuario_id')
                        ->from('usuarios')
                        ->join('personas', 'personas.persona_id', '=', 'usuarios.persona_id')
                        ->where('personas.dni', self::DNI_ADMIN);
                })
                ->delete();

            // Eliminar el usuario admin de la persona genérica
            DB::table('usuarios')
                ->where('persona_id', function ($query) {
                    $query->select('persona_id')
                        ->from('personas')
                        ->where('dni', self::DNI_ADMIN);
                })
                ->delete();

            // Eliminar la persona genérica
            DB::table('personas')
                ->where('dni', self::DNI_ADMIN)
                ->delete();
        });
    }
};
