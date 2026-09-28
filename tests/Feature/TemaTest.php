<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemaTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioAdmin(): Usuario
    {
        return Usuario::whereHas('persona', fn ($query) => $query->where('dni', Usuario::DNI_ADMIN))
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Tema de la interfaz (claro/oscuro)
    |--------------------------------------------------------------------------
    */

    public function test_el_tema_se_guarda_en_la_tabla_de_usuarios(): void
    {
        $usuario = $this->usuarioAdmin();

        $this->actingAs($usuario)
            ->postJson(route('usuarios.tema'), ['tema' => 'dark'])
            ->assertOk()
            ->assertJson(['tema' => 'dark']);

        $this->assertDatabaseHas('usuarios', [
            'usuario_id' => $usuario->usuario_id,
            'tema' => 'dark',
        ]);
    }

    public function test_el_tema_rechaza_valores_invalidos(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->postJson(route('usuarios.tema'), ['tema' => 'azul'])
            ->assertStatus(422);

        $this->actingAs($this->usuarioAdmin())
            ->postJson(route('usuarios.tema'), [])
            ->assertStatus(422);
    }

    public function test_el_layout_aplica_el_tema_guardado_en_el_usuario(): void
    {
        $usuario = $this->usuarioAdmin();
        $usuario->update(['tema' => 'dark']);

        $this->actingAs($usuario->fresh())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-bs-theme="dark"', false)
            ->assertSee('tema-oscuro.css', false);
    }

    public function test_los_formularios_de_busqueda_de_dni_ofrecen_el_boton_nuevo(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->get(route('afiliados.create'))
            ->assertOk()
            ->assertSee('id="btnRegistrarPersona"', false)
            ->assertSee('title="Buscar persona en el listado"', false)
            ->assertSee('title="Registrar persona nueva con este DNI"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Registro de persona a partir de un DNI inexistente
    |--------------------------------------------------------------------------
    */

    public function test_el_registro_de_persona_precarga_el_dni_recibido(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->get(route('personas.create', ['dni' => '12345678']))
            ->assertOk()
            ->assertSee('value="12345678"', false);
    }
}
