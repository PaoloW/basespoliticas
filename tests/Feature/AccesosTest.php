<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesosTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioAdmin(): Usuario
    {
        return Usuario::whereHas('persona', fn ($query) => $query->where('dni', Usuario::DNI_ADMIN))
            ->firstOrFail();
    }

    private function personaNueva(string $dni = '12345678'): Persona
    {
        return Persona::create([
            'persona_id' => (Persona::withTrashed()->max('persona_id') ?? 0) + 1,
            'dni' => $dni,
            'nombres' => 'Juan',
            'primer_apellido' => 'Quispe',
            'segundo_apellido' => 'Perez',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Fix 3.b: los accesos de conteo de votos están divididos en dos menús
    |--------------------------------------------------------------------------
    */

    public function test_los_accesos_de_conteo_de_votos_estan_divididos_en_dos_menus(): void
    {
        $this->assertDatabaseHas('menu', ['descripcion' => Menu::VER_CONTEOS]);
        $this->assertDatabaseHas('menu', ['descripcion' => Menu::REGISTRAR_CONTEOS]);
        $this->assertDatabaseMissing('menu', ['descripcion' => 'Gestión de votos']);
    }

    public function test_los_dos_menus_de_conteo_comparten_el_mismo_padre(): void
    {
        $ver = Menu::where('descripcion', Menu::VER_CONTEOS)->firstOrFail();
        $registrar = Menu::where('descripcion', Menu::REGISTRAR_CONTEOS)->firstOrFail();

        $this->assertSame($ver->menu_padre_id, $registrar->menu_padre_id);
        $this->assertNotSame($ver->menu_id, $registrar->menu_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Fix 2: registrar conteo no otorga afiliados ni otros accesos
    |--------------------------------------------------------------------------
    */

    public function test_al_crear_un_usuario_solo_se_guardan_los_menus_marcados(): void
    {
        $persona = $this->personaNueva();
        $registrar = Menu::where('descripcion', Menu::REGISTRAR_CONTEOS)->firstOrFail();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('usuarios.store'), [
                'persona_id' => $persona->persona_id,
                'clave' => 'secret123',
                'menu_ids' => [$registrar->menu_id],
            ])
            ->assertRedirect(route('usuarios.index'));

        $usuario = Usuario::where('persona_id', $persona->persona_id)->firstOrFail();

        $this->assertSame([$registrar->menu_id], $usuario->accesos()->pluck('menu_id')->all());

        // El acceso a "Afiliados a bases" no se otorga implícitamente.
        $this->assertFalse($usuario->tieneAccesoDescripcion(Menu::AFILIADOS_BASES));
        $this->assertFalse($usuario->tieneAcceso(Menu::where('descripcion', Menu::AFILIADOS_BASES)->firstOrFail()->menu_id));
    }

    public function test_al_editar_un_usuario_se_respetan_solo_los_menus_marcados(): void
    {
        $persona = $this->personaNueva('87654321');
        $registrar = Menu::where('descripcion', Menu::REGISTRAR_CONTEOS)->firstOrFail();

        $usuario = new Usuario();
        $usuario->persona_id = $persona->persona_id;
        $usuario->clave = bcrypt('secret123');
        $usuario->save();
        $usuario->accesos()->create(['menu_id' => Menu::where('descripcion', Menu::AFILIADOS_BASES)->firstOrFail()->menu_id]);

        // Se desmarca afiliados y se marca registrar conteo.
        $this->actingAs($this->usuarioAdmin())
            ->put(route('usuarios.update', $usuario), [
                'persona_id' => $persona->persona_id,
                'menu_ids' => [$registrar->menu_id],
            ])
            ->assertRedirect(route('usuarios.index'));

        $this->assertSame([$registrar->menu_id], $usuario->accesos()->pluck('menu_id')->all());
        $this->assertFalse($usuario->fresh()->tieneAccesoDescripcion(Menu::AFILIADOS_BASES));
    }

    /*
    |--------------------------------------------------------------------------
    | Menú lateral: cada usuario ve solo sus accesos
    |--------------------------------------------------------------------------
    */

    public function test_el_menu_lateral_no_muestra_afiliados_sin_el_acceso_correspondiente(): void
    {
        $persona = $this->personaNueva();
        $registrar = Menu::where('descripcion', Menu::REGISTRAR_CONTEOS)->firstOrFail();

        $usuario = new Usuario();
        $usuario->persona_id = $persona->persona_id;
        $usuario->clave = bcrypt('secret123');
        $usuario->save();
        $usuario->accesos()->create(['menu_id' => $registrar->menu_id]);

        $this->actingAs($usuario)
            ->get(route('votos.registrar'))
            ->assertOk()
            ->assertSee('Registrar conteo de votos')
            ->assertDontSee('href="'.route('afiliados.index').'"');
    }

    public function test_el_menu_lateral_no_muestra_conteos_sin_el_acceso_correspondiente(): void
    {
        $persona = $this->personaNueva('87654321');

        $usuario = new Usuario();
        $usuario->persona_id = $persona->persona_id;
        $usuario->clave = bcrypt('secret123');
        $usuario->save();

        $this->actingAs($usuario)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="'.route('votos.index').'"')
            ->assertDontSee('href="'.route('votos.registrar').'"');
    }
}
