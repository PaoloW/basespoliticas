<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * Registra los menús principales y sus submenús.
     * Es idempotente: si un menú ya existe, no lo duplica.
     */
    public function run(): void
    {
        // Estructura: menú padre => (icono + submenús)
        $menus = [
            'Gestión' => [
                'icono' => 'pi pi-briefcase',
                'hijos' => [
                    ['descripcion' => 'Usuarios', 'icono' => 'pi pi-users'],
                    ['descripcion' => 'Personas', 'icono' => 'pi pi-user'],
                    ['descripcion' => 'Centros de votación', 'icono' => 'pi pi-hand-fist'],
                    ['descripcion' => 'Mesas de votación', 'icono' => 'pi pi-table'],
                    ['descripcion' => 'Cargos', 'icono' => 'pi pi-briefcase'],
                    ['descripcion' => 'Bases', 'icono' => 'pi pi-database'],
                ],
            ],
            'Procesos' => [
                'icono' => 'pi pi-sync',
                'hijos' => [
                    ['descripcion' => 'Afiliados a bases', 'icono' => 'pi pi-user-plus'],
                    ['descripcion' => 'Personeros', 'icono' => 'pi pi-verified'],
                ],
            ],
            'Reportes' => [
                'icono' => 'pi pi-chart-bar',
                'hijos' => [
                    ['descripcion' => 'Reporte de conteos', 'icono' => 'pi pi-file'],
                    ['descripcion' => 'Reporte de bases', 'icono' => 'pi pi-file'],
                    ['descripcion' => 'Reporte de afiliados', 'icono' => 'pi pi-file'],
                ],
            ],
        ];

        DB::transaction(function () use ($menus) {
            foreach ($menus as $nombrePadre => $menu) {
                // Obtener o crear el menú padre
                $padre = DB::table('menu')->where('descripcion', $nombrePadre)->first();

                if (! $padre) {
                    $padreId = DB::table('menu')->insertGetId([
                        'menu_padre_id' => null,
                        'descripcion' => $nombrePadre,
                        'icono' => $menu['icono'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        'autor_id' => null,
                        'editor_id' => null,
                    ]);
                } else {
                    $padreId = $padre->menu_id;
                }

                // Insertar los submenús si aún no existen
                foreach ($menu['hijos'] as $hijo) {
                    $existe = DB::table('menu')
                        ->where('descripcion', $hijo['descripcion'])
                        ->where('menu_padre_id', $padreId)
                        ->exists();

                    if (! $existe) {
                        DB::table('menu')->insert([
                            'menu_padre_id' => $padreId,
                            'descripcion' => $hijo['descripcion'],
                            'icono' => $hijo['icono'],
                            'created_at' => now(),
                            'updated_at' => now(),
                            'autor_id' => null,
                            'editor_id' => null,
                        ]);
                    }
                }
            }
        });
    }
}
