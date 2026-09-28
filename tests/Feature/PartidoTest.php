<?php

namespace Tests\Feature;

use App\Models\Partido;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartidoTest extends TestCase
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
     * Solo un usuario autenticado puede ver el listado de partidos.
     */
    public function test_el_listado_de_partidos_requiere_sesion(): void
    {
        $this->get(route('partidos.index'))->assertRedirect(route('login'));
    }

    /**
     * El listado muestra los partidos registrados.
     */
    public function test_el_listado_muestra_los_partidos_registrados(): void
    {
        $partido = Partido::create(['nombre' => 'Partido Azul']);

        $this->actingAs($this->usuarioAdmin())
            ->get(route('partidos.index'))
            ->assertOk()
            ->assertSee($partido->nombre);
    }

    /**
     * El formulario de registro muestra el campo del nombre.
     */
    public function test_el_formulario_de_registro_muestra_el_campo_nombre(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->get(route('partidos.create'))
            ->assertOk()
            ->assertSee('Nombre del partido');
    }

    /**
     * Se puede registrar un partido con solo su nombre.
     */
    public function test_se_puede_registrar_un_partido(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->post(route('partidos.store'), ['nombre' => 'Partido Azul', 'orden' => 1])
            ->assertRedirect(route('partidos.index'));

        $this->assertDatabaseHas('partidos', ['nombre' => 'Partido Azul']);
    }

    /**
     * El nombre del partido es obligatorio.
     */
    public function test_el_nombre_del_partido_es_obligatorio(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->from(route('partidos.create'))
            ->post(route('partidos.store'), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');

        $this->assertDatabaseCount('partidos', 0);
    }

    /**
     * El formulario de edición muestra el nombre actual del partido.
     */
    public function test_el_formulario_de_edicion_muestra_el_nombre_actual(): void
    {
        $partido = Partido::create(['nombre' => 'Partido Azul']);

        $this->actingAs($this->usuarioAdmin())
            ->get(route('partidos.edit', $partido))
            ->assertOk()
            ->assertSee('Partido Azul');
    }

    /**
     * Se puede editar el nombre de un partido existente.
     */
    public function test_se_puede_editar_el_nombre_del_partido(): void
    {
        $partido = Partido::create(['nombre' => 'Partido Azul']);

        $this->actingAs($this->usuarioAdmin())
            ->put(route('partidos.update', $partido), ['nombre' => 'Partido Rojo', 'orden' => 1])
            ->assertRedirect(route('partidos.index'));

        $this->assertDatabaseHas('partidos', [
            'partido_id' => $partido->partido_id,
            'nombre' => 'Partido Rojo',
        ]);

        $this->assertSame('Partido Rojo', $partido->fresh()->nombre);
    }

    /**
     * Se puede eliminar (borrado lógico) un partido.
     */
    public function test_se_puede_eliminar_un_partido(): void
    {
        $partido = Partido::create(['nombre' => 'Partido Azul']);

        $this->actingAs($this->usuarioAdmin())
            ->delete(route('partidos.destroy', $partido))
            ->assertRedirect(route('partidos.index'));

        $this->assertSoftDeleted('partidos', ['partido_id' => $partido->partido_id]);
    }

    /**
     * El modelo expone su representación para el frontend.
     */
    public function test_el_modelo_expone_su_representacion(): void
    {
        $partido = Partido::create(['nombre' => 'Partido Azul']);

        $this->assertSame([
            'partido_id' => $partido->partido_id,
            'nombre' => 'Partido Azul',
        ], $partido->toRepresentacion());
    }
}
