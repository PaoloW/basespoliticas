<?php

namespace Tests\Feature;

use App\Models\Centro;
use App\Models\Mesa;
use App\Models\Partido;
use App\Models\Persona;
use App\Models\Personero;
use App\Models\Usuario;
use App\Models\Voto;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotoTest extends TestCase
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

    /**
     * Crea una persona con id manual (la tabla no usa autoincremento).
     */
    private function persona(string $dni = '12345678', string $nombres = 'Juan', string $apellidos = 'Quispe'): Persona
    {
        return Persona::create([
            'persona_id' => (Persona::withTrashed()->max('persona_id') ?? 0) + 1,
            'dni' => $dni,
            'nombres' => $nombres,
            'primer_apellido' => $apellidos,
            'segundo_apellido' => 'Perez',
        ]);
    }

    private function partido(string $nombre = 'Partido Azul'): Partido
    {
        return Partido::create(['nombre' => $nombre]);
    }

    /**
     * Crea centro, mesa y personero para la persona dada.
     */
    private function personero(?Persona $persona = null): Personero
    {
        $persona ??= $this->persona();

        $centro = Centro::create(['descripcion' => 'IE San Martin '.$persona->persona_id]);
        $mesa = Mesa::create(['descripcion' => 1, 'centro_id' => $centro->centro_id]);

        return Personero::create([
            'persona_id' => $persona->persona_id,
            'mesa_id' => $mesa->mesa_id,
        ]);
    }

    /**
     * Usuario común (no administrador) vinculado a la persona dada.
     */
    private function usuarioComun(Persona $persona): Usuario
    {
        $usuario = new Usuario();
        $usuario->persona_id = $persona->persona_id;
        $usuario->clave = bcrypt('secret123');
        $usuario->save();

        return $usuario;
    }

    /*
    |--------------------------------------------------------------------------
    | Reporte (ver conteo)
    |--------------------------------------------------------------------------
    */

    public function test_el_reporte_de_conteos_requiere_sesion(): void
    {
        $this->get(route('votos.index'))->assertRedirect(route('login'));
    }

    public function test_el_reporte_muestra_al_personero_su_mesa_y_el_total_de_votos(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        Voto::create([
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.index'))
            ->assertOk()
            ->assertSee($personero->persona->apellidoNombre())
            ->assertSee('Mesa N° 1')
            ->assertSee('IE San Martin')
            ->assertSee('120');
    }

    public function test_el_reporte_solo_muestra_los_personeros_con_conteo_registrado(): void
    {
        $personero = $this->personero();

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.index'))
            ->assertOk()
            ->assertDontSee($personero->persona->apellidoNombre());
    }

    /*
    |--------------------------------------------------------------------------
    | Registrar conteo (formulario directo)
    |--------------------------------------------------------------------------
    */

    public function test_el_formulario_de_registro_requiere_sesion(): void
    {
        $this->get(route('votos.registrar'))->assertRedirect(route('login'));
    }

    public function test_el_formulario_directo_muestra_los_partidos_y_el_selector_de_personeros(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.registrar'))
            ->assertOk()
            ->assertSee('name="personero_id"', false)
            ->assertSee($partido->nombre)
            ->assertSee($personero->persona->apellidoNombre());
    }

    public function test_un_personero_ve_su_propio_formulario_sin_selector_de_otros_personeros(): void
    {
        $personero = $this->personero();
        $usuario = $this->usuarioComun($personero->persona);

        $this->actingAs($usuario)
            ->get(route('votos.registrar'))
            ->assertOk()
            ->assertSee($personero->persona->apellidoNombre())
            ->assertSee('<input type="hidden" name="personero_id"', false)
            ->assertDontSee('<select class="form-select" name="personero_id"', false);
    }

    public function test_se_registra_el_conteo_por_partido_del_personero(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'personero_id' => $personero->personero_id,
                'votos' => [$partido->partido_id => 150],
            ])
            ->assertRedirect(route('votos.index'));

        $this->assertDatabaseCount('votos', 1);
        $this->assertDatabaseHas('votos', [
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 150,
        ]);
    }

    public function test_un_personero_registra_su_propio_conteo_sin_indicar_personero(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();
        $usuario = $this->usuarioComun($personero->persona);

        // Aunque se envíe otro personero, manda el del usuario autenticado.
        $otro = $this->personero($this->persona('87654321', 'Maria', 'Lopez'));

        $this->actingAs($usuario)
            ->post(route('votos.registrar'), [
                'personero_id' => $otro->personero_id,
                'votos' => [$partido->partido_id => 90],
            ])
            ->assertRedirect(route('votos.index'));

        $this->assertDatabaseHas('votos', [
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 90,
        ]);
        $this->assertDatabaseMissing('votos', ['personero_id' => $otro->personero_id]);
    }

    public function test_se_exige_seleccionar_el_personero(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.registrar'))
            ->post(route('votos.registrar'), ['votos' => [1 => 10]])
            ->assertSessionHasErrors('personero_id');

        $this->assertDatabaseCount('votos', 0);
    }

    public function test_se_exige_el_arreglo_de_votos(): void
    {
        $personero = $this->personero();

        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.registrar'))
            ->post(route('votos.registrar'), ['personero_id' => $personero->personero_id])
            ->assertSessionHasErrors('votos');

        $this->assertDatabaseCount('votos', 0);
    }

    public function test_los_votos_no_pueden_ser_negativos(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.registrar'))
            ->post(route('votos.registrar'), [
                'personero_id' => $personero->personero_id,
                'votos' => [$partido->partido_id => -1],
            ])
            ->assertSessionHasErrors('votos.'.$partido->partido_id);

        $this->assertDatabaseCount('votos', 0);
    }

    public function test_no_se_duplica_el_conteo_de_un_mismo_partido_y_personero(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'personero_id' => $personero->personero_id,
                'votos' => [$partido->partido_id => 120],
            ]);

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'personero_id' => $personero->personero_id,
                'votos' => [$partido->partido_id => 999],
            ]);

        $this->assertDatabaseCount('votos', 1);
        $this->assertDatabaseHas('votos', [
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 999,
        ]);
    }

    public function test_la_base_de_datos_impide_duplicar_partido_y_personero(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        Voto::create([
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 120,
        ]);

        $this->expectException(QueryException::class);

        Voto::create([
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 130,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Edición y eliminación (administrador)
    |--------------------------------------------------------------------------
    */

    public function test_el_formulario_de_edicion_muestra_personero_mesa_y_conteo(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $voto = Voto::create([
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.edit', $voto))
            ->assertOk()
            ->assertSee('Partido Azul')
            ->assertSee($personero->persona->apellidoNombre())
            ->assertSee('Mesa N° 1')
            ->assertSee('value="120"', false);
    }

    public function test_se_pueden_actualizar_los_votos(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $voto = Voto::create([
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->put(route('votos.update', $voto), [
                'votos' => [$partido->partido_id => 250],
            ])
            ->assertRedirect(route('votos.index'));

        $this->assertDatabaseHas('votos', [
            'voto_id' => $voto->voto_id,
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 250,
        ]);
    }

    public function test_se_puede_eliminar_un_registro_de_votos(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $voto = Voto::create([
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->delete(route('votos.destroy', $voto))
            ->assertRedirect(route('votos.index'));

        $this->assertSoftDeleted('votos', ['voto_id' => $voto->voto_id]);
    }

    public function test_se_puede_volver_a_registrar_un_conteo_eliminado(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $voto = Voto::create([
            'personero_id' => $personero->personero_id,
            'partido_id' => $partido->partido_id,
            'votos' => 120,
        ]);
        $voto->delete();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'personero_id' => $personero->personero_id,
                'votos' => [$partido->partido_id => 300],
            ])
            ->assertRedirect(route('votos.index'));

        $this->assertDatabaseCount('votos', 1);
        $this->assertDatabaseHas('votos', [
            'voto_id' => $voto->voto_id,
            'votos' => 300,
            'deleted_at' => null,
        ]);
    }
}
