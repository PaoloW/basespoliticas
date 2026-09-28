<?php

namespace Tests\Feature;

use App\Models\Afiliado;
use App\Models\Base;
use App\Models\Cargo;
use App\Models\Centro;
use App\Models\Menu;
use App\Models\Mesa;
use App\Models\Persona;
use App\Models\Personero;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
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

    /**
     * Crea una persona con id manual (la tabla no usa autoincremento).
     */
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

    /**
     * Afiliación vigente de una persona (base + cargo).
     */
    private function afiliar(Persona $persona): Afiliado
    {
        return Afiliado::create([
            'persona_id' => $persona->persona_id,
            'base_id' => Base::create(['descripcion' => 'Base '.uniqid()])->base_id,
            'cargo_id' => Cargo::create(['descripcion' => 'Vocal'])->cargo_id,
        ]);
    }

    private function mesa(int $descripcion = 1): Mesa
    {
        $centro = Centro::create(['descripcion' => 'IE '.uniqid()]);

        return Mesa::create(['descripcion' => $descripcion, 'centro_id' => $centro->centro_id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Listado
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Registro (solo con persona; la afiliación es opcional)
    |--------------------------------------------------------------------------
    */

    public function test_se_registra_un_personero_sin_afiliacion(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('personeros.store'), [
                'persona_id' => $persona->persona_id,
                'mesa_id' => $mesa->mesa_id,
            ])
            ->assertRedirect(route('personeros.index'));

        $this->assertDatabaseHas('personeros', [
            'persona_id' => $persona->persona_id,
            'mesa_id' => $mesa->mesa_id,
        ]);

        // No se necesita afiliación para ser personero.
        $this->assertSame(0, Afiliado::count());
    }

    public function test_se_registra_un_personero_con_afiliacion(): void
    {
        $persona = $this->persona();
        $this->afiliar($persona);
        $mesa = $this->mesa();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('personeros.store'), [
                'persona_id' => $persona->persona_id,
                'mesa_id' => $mesa->mesa_id,
            ])
            ->assertRedirect(route('personeros.index'));

        $personero = Personero::where('persona_id', $persona->persona_id)->firstOrFail();

        // El personero trabaja solo con la persona: sin columna de afiliación.
        $this->assertFalse($personero->getConnection()->getSchemaBuilder()->hasColumn('personeros', 'afiliado_id'));

        // La afiliación se consulta a través de la persona.
        $this->assertNotSame('Sin afiliación', $personero->descripcionAfiliacion());
    }

    public function test_al_marcar_crear_usuario_se_crea_su_cuenta_con_acceso_a_registrar_conteos(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('personeros.store'), [
                'persona_id' => $persona->persona_id,
                'mesa_id' => $mesa->mesa_id,
                'crear_usuario' => 1,
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

    public function test_sin_marcar_crear_usuario_no_se_crea_cuenta(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('personeros.store'), [
                'persona_id' => $persona->persona_id,
                'mesa_id' => $mesa->mesa_id,
            ])
            ->assertRedirect(route('personeros.index'));

        $this->assertSame(0, Usuario::where('persona_id', $persona->persona_id)->count());
    }

    public function test_no_se_puede_registrar_la_misma_persona_dos_veces(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();
        $otraMesa = $this->mesa(2);

        Personero::create(['persona_id' => $persona->persona_id, 'mesa_id' => $mesa->mesa_id]);

        $this->actingAs($this->usuarioAdmin())
            ->from(route('personeros.create'))
            ->post(route('personeros.store'), [
                'persona_id' => $persona->persona_id,
                'mesa_id' => $otraMesa->mesa_id,
            ])
            ->assertSessionHasErrors('persona_id');

        $this->assertDatabaseCount('personeros', 1);
    }

    public function test_la_base_de_datos_impide_duplicar_la_persona_del_personero(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();
        $otraMesa = $this->mesa(2);

        Personero::create(['persona_id' => $persona->persona_id, 'mesa_id' => $mesa->mesa_id]);

        // El índice único solo acepta un personero vigente por persona.
        $this->expectException(UniqueConstraintViolationException::class);

        Personero::create(['persona_id' => $persona->persona_id, 'mesa_id' => $otraMesa->mesa_id]);
    }

    public function test_no_se_puede_asignar_una_mesa_que_ya_tiene_personero(): void
    {
        $mesa = $this->mesa();
        Personero::create([
            'persona_id' => $this->persona('11111111')->persona_id,
            'mesa_id' => $mesa->mesa_id,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->from(route('personeros.create'))
            ->post(route('personeros.store'), [
                'persona_id' => $this->persona('22222222')->persona_id,
                'mesa_id' => $mesa->mesa_id,
            ])
            ->assertSessionHasErrors('mesa_id');

        $this->assertDatabaseCount('personeros', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Búsqueda por DNI y modal de personas
    |--------------------------------------------------------------------------
    */

    public function test_la_busqueda_por_dni_devuelve_la_persona_con_su_afiliacion(): void
    {
        $persona = $this->persona();
        $afiliado = $this->afiliar($persona);

        $this->actingAs($this->usuarioAdmin())
            ->getJson(route('personeros.buscarPersona', ['dni' => $persona->dni]))
            ->assertOk()
            ->assertJson([
                'persona_id' => $persona->persona_id,
                'dni' => $persona->dni,
                'nombre_completo' => $persona->apellidoNombre(),
                'afiliacion' => $afiliado->descripcionAfiliacion(),
            ]);
    }

    public function test_la_busqueda_por_dni_informa_si_la_persona_no_tiene_afiliacion(): void
    {
        $persona = $this->persona();

        $this->actingAs($this->usuarioAdmin())
            ->getJson(route('personeros.buscarPersona', ['dni' => $persona->dni]))
            ->assertOk()
            ->assertJson(['afiliacion' => 'Sin afiliación']);
    }

    public function test_la_busqueda_por_dni_avisa_cuando_la_persona_ya_es_personero(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();
        Personero::create(['persona_id' => $persona->persona_id, 'mesa_id' => $mesa->mesa_id]);

        $this->actingAs($this->usuarioAdmin())
            ->getJson(route('personeros.buscarPersona', ['dni' => $persona->dni]))
            ->assertStatus(409);
    }

    public function test_la_busqueda_por_dni_no_encontrado_permite_registrar_la_persona(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->getJson(route('personeros.buscarPersona', ['dni' => '99999999']))
            ->assertStatus(404);
    }

    public function test_el_modal_lista_personas_con_y_sin_afiliacion(): void
    {
        $conAfiliacion = $this->persona('33333333');
        $this->afiliar($conAfiliacion);
        $this->persona('44444444');

        $respuesta = $this->actingAs($this->usuarioAdmin())
            ->getJson(route('personeros.personas'))
            ->assertOk();

        $respuesta->assertJsonFragment(['dni' => '33333333']);
        $respuesta->assertJsonFragment(['dni' => '44444444']);
        $respuesta->assertJsonFragment(['afiliacion' => 'Sin afiliación']);
    }

    public function test_el_modal_no_lista_personas_que_ya_son_personeros(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();
        Personero::create(['persona_id' => $persona->persona_id, 'mesa_id' => $mesa->mesa_id]);

        $this->actingAs($this->usuarioAdmin())
            ->getJson(route('personeros.personas'))
            ->assertOk()
            ->assertJsonMissing(['dni' => $persona->dni]);
    }

    /*
    |--------------------------------------------------------------------------
    | Edición y eliminación
    |--------------------------------------------------------------------------
    */

    public function test_se_puede_editar_la_mesa_del_personero(): void
    {
        $persona = $this->persona();
        $mesa = $this->mesa();
        $otraMesa = $this->mesa(2);

        $personero = Personero::create([
            'persona_id' => $persona->persona_id,
            'mesa_id' => $mesa->mesa_id,
        ]);

        // El formulario de edición muestra la persona (bloqueada) y su mesa.
        $this->actingAs($this->usuarioAdmin())
            ->get(route('personeros.edit', $personero))
            ->assertOk()
            ->assertSee($persona->apellidoNombre())
            ->assertSee('Mesa N° 1');

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

    /*
    |--------------------------------------------------------------------------
    | Continuidad del flujo: registrar la persona y volver al formulario
    |--------------------------------------------------------------------------
    */

    public function test_el_formulario_retoma_la_persona_recien_creada(): void
    {
        $persona = $this->persona();

        $this->actingAs($this->usuarioAdmin())
            ->get(route('personeros.create', ['dni' => $persona->dni, 'persona_id' => $persona->persona_id]))
            ->assertOk()
            ->assertSee('value="'.$persona->persona_id.'"', false)
            ->assertSee($persona->apellidoNombre())
            // Selector compartido de personas (modal + script).
            ->assertSee('selector-persona-modal', false)
            ->assertSee('persona:limpiar', false);
    }

    public function test_al_registrar_una_persona_desde_personeros_se_vuelve_con_el_dni_y_la_persona(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->post(route('personas.store'), [
                'origen' => 'personeros',
                'dni' => '55555555',
                'nombres' => 'Ana',
                'primer_apellido' => 'Torres',
            ])
            ->assertRedirect(route('personeros.create', ['dni' => '55555555', 'persona_id' => Persona::max('persona_id')]));

        $this->assertDatabaseHas('personas', ['dni' => '55555555']);
    }
}
