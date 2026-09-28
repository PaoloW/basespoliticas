<?php

namespace Tests\Feature;

use App\Models\Partido;
use App\Models\Persona;
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

    private function persona(string $dni, string $nombres, string $apellidos, ?string $codigoMesa = null): Persona
    {
        return Persona::create([
            'persona_id' => (Persona::withTrashed()->max('persona_id') ?? 0) + 1,
            'dni' => $dni,
            'nombres' => $nombres,
            'primer_apellido' => $apellidos,
            'segundo_apellido' => 'Perez',
            'codigo_mesa' => $codigoMesa,
        ]);
    }

    private function partido(string $nombre = 'Partido Azul'): Partido
    {
        return Partido::create(['nombre' => $nombre]);
    }

    /**
     * Solo un usuario autenticado puede ver el listado de votos.
     */
    public function test_el_listado_de_votos_requiere_sesion(): void
    {
        $this->get(route('votos.index'))->assertRedirect(route('login'));
    }

    /**
     * El listado muestra la persona, su código de mesa y el voto registrado.
     */
    public function test_el_listado_muestra_la_persona_su_codigo_de_mesa_y_los_votos(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        Voto::create([
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.index'))
            ->assertOk()
            ->assertSee($persona->apellidoNombre())
            ->assertSee('MZ-01')
            ->assertSee('Partido Azul')
            ->assertSee('120');
    }

    /**
     * El formulario de registro tiene los dos selects y el campo de votos.
     */
    public function test_el_formulario_de_registro_muestra_los_selects_y_el_campo_de_votos(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.create'))
            ->assertOk()
            ->assertSee('name="partido_id"', false)
            ->assertSee('name="persona_id"', false)
            ->assertSee('name="votos"', false)
            ->assertSee($partido->nombre)
            ->assertSee($persona->apellidoNombre())
            ->assertSee('Mesa '.$persona->codigo_mesa);
    }

    /**
     * Se registran los votos del partido y la persona seleccionados.
     */
    public function test_se_registran_los_votos_del_partido_y_la_persona(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.store'), [
                'partido_id' => $partido->partido_id,
                'persona_id' => $persona->persona_id,
                'votos' => 150,
            ])
            ->assertRedirect(route('votos.index'));

        $this->assertDatabaseCount('votos', 1);
        $this->assertDatabaseHas('votos', [
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 150,
        ]);
    }

    /**
     * Se exige seleccionar el partido.
     */
    public function test_se_exige_seleccionar_el_partido(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');

        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.create'))
            ->post(route('votos.store'), ['persona_id' => $persona->persona_id, 'votos' => 10])
            ->assertSessionHasErrors('partido_id');

        $this->assertDatabaseCount('votos', 0);
    }

    /**
     * Se exige seleccionar la persona.
     */
    public function test_se_exige_seleccionar_la_persona(): void
    {
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.create'))
            ->post(route('votos.store'), ['partido_id' => $partido->partido_id, 'votos' => 10])
            ->assertSessionHasErrors('persona_id');

        $this->assertDatabaseCount('votos', 0);
    }

    /**
     * Se exige la cantidad de votos.
     */
    public function test_se_exige_la_cantidad_de_votos(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.create'))
            ->post(route('votos.store'), [
                'partido_id' => $partido->partido_id,
                'persona_id' => $persona->persona_id,
            ])
            ->assertSessionHasErrors('votos');

        $this->assertDatabaseCount('votos', 0);
    }

    /**
     * Los votos no pueden ser negativos.
     */
    public function test_los_votos_no_pueden_ser_negativos(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.create'))
            ->post(route('votos.store'), [
                'partido_id' => $partido->partido_id,
                'persona_id' => $persona->persona_id,
                'votos' => -1,
            ])
            ->assertSessionHasErrors('votos');

        $this->assertDatabaseCount('votos', 0);
    }

    /**
     * Un partido no se registra dos veces para la misma persona.
     */
    public function test_no_se_registra_dos_veces_el_mismo_partido_y_persona(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        Voto::create([
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.create'))
            ->post(route('votos.store'), [
                'partido_id' => $partido->partido_id,
                'persona_id' => $persona->persona_id,
                'votos' => 999,
            ])
            ->assertSessionHasErrors('persona_id');

        $this->assertDatabaseCount('votos', 1);
        $this->assertDatabaseHas('votos', [
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 120,
        ]);
    }

    /**
     * El índice único de la tabla impide duplicar partido y persona.
     */
    public function test_la_base_de_datos_impide_duplicar_partido_y_persona(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        Voto::create([
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 120,
        ]);

        $this->expectException(QueryException::class);

        Voto::create([
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 130,
        ]);
    }

    /**
     * El formulario de edición muestra el partido, la persona y su código de mesa.
     */
    public function test_el_formulario_de_edicion_muestra_partido_persona_y_codigo_de_mesa(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        $voto = Voto::create([
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.edit', $voto))
            ->assertOk()
            ->assertSee('Partido Azul')
            ->assertSee($persona->apellidoNombre())
            ->assertSee('DNI: '.$persona->dni)
            ->assertSee('MZ-01')
            ->assertSee('value="120"', false);
    }

    /**
     * Se puede actualizar la cantidad de votos actuales.
     */
    public function test_se_pueden_actualizar_los_votos(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        $voto = Voto::create([
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->put(route('votos.update', $voto), ['votos' => 250])
            ->assertRedirect(route('votos.index'));

        $this->assertDatabaseHas('votos', [
            'voto_id' => $voto->voto_id,
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 250,
        ]);
    }

    /**
     * Se puede eliminar (borrado lógico) un registro de votos.
     */
    public function test_se_puede_eliminar_un_registro_de_votos(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        $voto = Voto::create([
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->delete(route('votos.destroy', $voto))
            ->assertRedirect(route('votos.index'));

        $this->assertSoftDeleted('votos', ['voto_id' => $voto->voto_id]);
    }

    /**
     * Un registro eliminado se puede volver a registrar: se reactiva con los votos nuevos.
     */
    public function test_se_puede_volver_a_registrar_un_partido_y_persona_eliminados(): void
    {
        $persona = $this->persona('12345678', 'Juan', 'Quispe', 'MZ-01');
        $partido = $this->partido();

        $voto = Voto::create([
            'partido_id' => $partido->partido_id,
            'persona_id' => $persona->persona_id,
            'votos' => 120,
        ]);
        $voto->delete();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.store'), [
                'partido_id' => $partido->partido_id,
                'persona_id' => $persona->persona_id,
                'votos' => 300,
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
