<?php

namespace Tests\Feature;

use App\Models\Centro;
use App\Models\Menu;
use App\Models\Mesa;
use App\Models\Persona;
use App\Models\Personero;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersoneroTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Usuario administrador creado por las migraciones (DNI 00000000).
     */
    private function usuarioAdmin(): Usuario
    {
        return Usuario::whereHas('persona', fn ($query) => $query->where('dni', Usuario::DNI_ADMIN))
            ->firstOrFail();
    }

    private function persona(string $dni = '12345678'): Persona
    {
        return Persona::create([
            'persona_id' => (Persona::withTrashed()->max('persona_id') ?? 0) + 1,
            'dni' => $dni,
            'nombres' => 'Juan',
            'primer_apellido' => 'Quispe',
            'segundo_apellido' => 'Perez',
        ]);
    }

    private function mesa(): Mesa
    {
        $centro = Centro::create(['descripcion' => 'IE '.uniqid()]);

        return Mesa::create(['descripcion' => 1, 'centro_id' => $centro->centro_id]);
    }

    public function test_el_listado_requiere_sesion(): void
    {
        $this->get(route('personeros.index'))->assertRedirect(route('login'));
    }

    public function test_el_listado_muestra_los_personeros_registrados(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();

        Personero::create(['persona_id' => $persona->persona_id, 'mesa_id' => $mesa->mesa_id]);

        $this->actingAs($this->usuarioAdmin())
            ->get(route('personeros.index'))
            ->assertOk()
            ->assertSee($persona->apellidoNombre());
    }

    public function test_al_registrar_un_personero_se_crea_su_usuario_con_acceso_a_registrar_conteos(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('personeros.store'), [
                'persona_id' => $persona->persona_id,
                'mesa_id' => $mesa->mesa_id,
            ])
            ->assertRedirect(route('personeros.index'));

        $usuario = Usuario::where('persona_id', $persona->persona_id)->firstOrFail();

        // La contraseña inicial es el DNI de la persona.
        $this->assertTrue($usuario->verificarClave($persona->dni));

        // Acceso por defecto al formulario de conteo de votos.
        $menu = Menu::where('descripcion', Menu::REGISTRAR_CONTEOS)->firstOrFail();
        $this->assertTrue($usuario->tieneAcceso($menu->menu_id));

        // No recibe otros accesos por el solo hecho de ser personero.
        $this->assertSame($usuario->accesos()->pluck('menu_id')->all(), [$menu->menu_id]);
    }

    public function test_no_se_puede_registrar_la_misma_persona_dos_veces(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();

        Personero::create(['persona_id' => $persona->persona_id, 'mesa_id' => $mesa->mesa_id]);

        $this->actingAs($this->usuarioAdmin())
            ->from(route('personeros.create'))
            ->post(route('personeros.store'), [
                'persona_id' => $persona->persona_id,
                'mesa_id' => $mesa->mesa_id,
            ])
            ->assertSessionHasErrors('persona_id');

        $this->assertDatabaseCount('personeros', 1);
    }

    public function test_la_base_de_datos_impide_duplicar_la_persona_del_personero(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();

        Personero::create(['persona_id' => $persona->persona_id, 'mesa_id' => $mesa->mesa_id]);

        // El índice único de personeros solo acepta una fila vigente por persona.
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Personero::create(['persona_id' => $persona->persona_id, 'mesa_id' => $mesa->mesa_id]);
    }

    public function test_se_puede_editar_la_mesa_del_personero(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();
        $otraMesa = $this->mesa();

        $personero = Personero::create([
            'persona_id' => $persona->persona_id,
            'mesa_id' => $mesa->mesa_id,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->put(route('personeros.update', $personero), ['mesa_id' => $otraMesa->mesa_id])
            ->assertRedirect(route('personeros.index'));

        $this->assertDatabaseHas('personeros', [
            'personero_id' => $personero->personero_id,
            'mesa_id' => $otraMesa->mesa_id,
        ]);
    }

    public function test_se_puede_eliminar_un_personero(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();

        $personero = Personero::create([
            'persona_id' => $persona->persona_id,
            'mesa_id' => $mesa->mesa_id,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->delete(route('personeros.destroy', $personero))
            ->assertRedirect(route('personeros.index'));

        $this->assertSoftDeleted('personeros', ['personero_id' => $personero->personero_id]);
    }
}
