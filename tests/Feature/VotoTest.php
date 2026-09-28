<?php

namespace Tests\Feature;

use App\Models\Acceso;
use App\Models\Afiliado;
use App\Models\Base;
use App\Models\Cargo;
use App\Models\Centro;
use App\Models\Menu;
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
    private function persona(string $dni = '12345678', string $nombres = 'Juan'): Persona
    {
        return Persona::create([
            'persona_id' => (Persona::withTrashed()->max('persona_id') ?? 0) + 1,
            'dni' => $dni,
            'nombres' => $nombres,
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

    private function partido(string $nombre = 'Partido Azul'): Partido
    {
        return Partido::create(['nombre' => $nombre]);
    }

    /**
     * Crea la mesa con su centro de votación.
     */
    private function mesa(int $descripcion = 1): Mesa
    {
        $centro = Centro::create(['descripcion' => 'IE San Martin '.uniqid()]);

        return Mesa::create(['descripcion' => $descripcion, 'centro_id' => $centro->centro_id]);
    }

    /**
     * Personero de una mesa (la afiliación de su persona es opcional).
     */
    private function personero(?Mesa $mesa = null, ?Persona $persona = null): Personero
    {
        $mesa ??= $this->mesa();
        $persona ??= $this->persona();

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

    /**
     * Usuario con el menú indicado (por defecto "Ver conteo de votos").
     */
    private function usuarioConAcceso(Persona $persona, string $menu = Menu::VER_CONTEOS): Usuario
    {
        $usuario = $this->usuarioComun($persona);

        Acceso::create([
            'usuario_id' => $usuario->usuario_id,
            'menu_id' => Menu::where('descripcion', $menu)->firstOrFail()->menu_id,
        ]);

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

    public function test_el_reporte_muestra_la_mesa_el_personero_y_el_total_de_votos(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        Voto::create([
            'mesa_id' => $personero->mesa_id,
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

    public function test_el_reporte_solo_muestra_las_mesas_con_conteo_registrado(): void
    {
        $personero = $this->personero();

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.index'))
            ->assertOk()
            ->assertDontSee($personero->persona->apellidoNombre());
    }

    public function test_un_personero_solo_ve_su_propia_mesa(): void
    {
        $personaSesion = $this->persona('11111111', 'Ana');
        $personero = $this->personero(null, $personaSesion);
        $partido = $this->partido();

        Voto::create([
            'mesa_id' => $personero->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 50,
        ]);

        // Otra mesa con conteo y otro personero.
        $otroPersonero = $this->personero($this->mesa(2));
        Voto::create([
            'mesa_id' => $otroPersonero->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 77,
        ]);

        $this->actingAs($this->usuarioConAcceso($personaSesion))
            ->get(route('votos.index'))
            ->assertOk()
            ->assertSee('Mesa N° 1')
            ->assertDontSee('Mesa N° 2');
    }

    /*
    |--------------------------------------------------------------------------
    | Registro del conteo (la persona del personero puede no tener afiliación)
    |--------------------------------------------------------------------------
    */

    public function test_el_formulario_de_registro_requiere_sesion(): void
    {
        $this->get(route('votos.registrar'))->assertRedirect(route('login'));
    }

    public function test_el_formulario_directo_muestra_los_partidos(): void
    {
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.registrar'))
            ->assertOk()
            ->assertSee($partido->nombre)
            ->assertSee('name="persona_id"', false)
            // Selector compartido de personas (markup + script).
            ->assertSee('selector-persona-modal', false)
            ->assertSee('persona:seleccionada', false);
    }

    public function test_el_formulario_con_mesa_muestra_su_personero_y_sus_conteos(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        Voto::create([
            'mesa_id' => $personero->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 45,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.registrar', ['mesa_id' => $personero->mesa_id]))
            ->assertOk()
            ->assertSee($personero->persona->apellidoNombre())
            ->assertSee('value="45"', false);
    }

    public function test_se_registra_el_conteo_por_partido_de_la_mesa(): void
    {
        $mesa = $this->mesa();
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'mesa_id' => $mesa->mesa_id,
                'votos' => [$partido->partido_id => 150],
            ])
            ->assertRedirect(route('votos.index'));

        $this->assertDatabaseCount('votos', 1);
        $this->assertDatabaseHas('votos', [
            'mesa_id' => $mesa->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 150,
        ]);
    }

    public function test_un_personero_registra_su_propio_conteo_sin_indicar_mesa(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();
        $usuario = $this->usuarioComun($personero->persona);
        $otraMesa = $this->mesa(2);

        // Aunque se envíe otra mesa, manda la del personero autenticado.
        $this->actingAs($usuario)
            ->post(route('votos.registrar'), [
                'mesa_id' => $otraMesa->mesa_id,
                'votos' => [$partido->partido_id => 90],
            ])
            ->assertRedirect(route('votos.index'));

        $this->assertDatabaseHas('votos', [
            'mesa_id' => $personero->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 90,
        ]);
        $this->assertDatabaseMissing('votos', ['mesa_id' => $otraMesa->mesa_id]);
    }

    public function test_se_exige_la_mesa(): void
    {
        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.registrar'))
            ->post(route('votos.registrar'), ['votos' => [1 => 10]])
            ->assertSessionHasErrors('mesa_id');

        $this->assertDatabaseCount('votos', 0);
    }

    public function test_se_exige_el_arreglo_de_votos(): void
    {
        $mesa = $this->mesa();

        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.registrar'))
            ->post(route('votos.registrar'), ['mesa_id' => $mesa->mesa_id])
            ->assertSessionHasErrors('votos');

        $this->assertDatabaseCount('votos', 0);
    }

    public function test_los_votos_no_pueden_ser_negativos(): void
    {
        $mesa = $this->mesa();
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->from(route('votos.registrar'))
            ->post(route('votos.registrar'), [
                'mesa_id' => $mesa->mesa_id,
                'votos' => [$partido->partido_id => -1],
            ])
            ->assertSessionHasErrors('votos.'.$partido->partido_id);

        $this->assertDatabaseCount('votos', 0);
    }

    public function test_no_se_duplica_el_conteo_de_un_mismo_partido_y_mesa(): void
    {
        $mesa = $this->mesa();
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'mesa_id' => $mesa->mesa_id,
                'votos' => [$partido->partido_id => 120],
            ]);

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'mesa_id' => $mesa->mesa_id,
                'votos' => [$partido->partido_id => 999],
            ]);

        $this->assertDatabaseCount('votos', 1);
        $this->assertDatabaseHas('votos', [
            'mesa_id' => $mesa->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 999,
        ]);
    }

    public function test_la_base_de_datos_impide_duplicar_partido_y_mesa(): void
    {
        $mesa = $this->mesa();
        $partido = $this->partido();

        Voto::create([
            'mesa_id' => $mesa->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 120,
        ]);

        $this->expectException(QueryException::class);

        Voto::create([
            'mesa_id' => $mesa->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 130,
        ]);
    }

    public function test_al_guardar_el_conteo_se_registra_el_personero_de_la_mesa(): void
    {
        $mesa = $this->mesa();
        $persona = $this->persona();
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'mesa_id' => $mesa->mesa_id,
                'persona_id' => $persona->persona_id,
                'votos' => [$partido->partido_id => 33],
            ])
            ->assertRedirect(route('votos.index'));

        // La persona no necesita afiliación para quedar como personero.
        $this->assertDatabaseHas('personeros', [
            'persona_id' => $persona->persona_id,
            'mesa_id' => $mesa->mesa_id,
        ]);
        $this->assertSame(0, Afiliado::count());
    }

    public function test_no_se_reemplaza_el_personero_de_una_mesa_que_ya_lo_tiene(): void
    {
        $personero = $this->personero();
        $otraPersona = $this->persona('87654321', 'Maria');
        $partido = $this->partido();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'mesa_id' => $personero->mesa_id,
                'persona_id' => $otraPersona->persona_id,
                'votos' => [$partido->partido_id => 10],
            ])
            ->assertRedirect(route('votos.index'));

        $this->assertDatabaseCount('personeros', 1);
        $this->assertDatabaseHas('personeros', ['personero_id' => $personero->personero_id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Búsqueda por mesa, por DNI y modal de personas
    |--------------------------------------------------------------------------
    */

    public function test_la_busqueda_de_mesa_devuelve_sus_datos_y_conteos(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        Voto::create([
            'mesa_id' => $personero->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 12,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->getJson(route('votos.buscarMesa', ['mesa' => 1]))
            ->assertOk()
            ->assertJson([
                'mesa_id' => $personero->mesa_id,
                'mesa' => 'Mesa N° 1',
                'tiene_personero' => true,
                'persona_id' => $personero->persona_id,
                'conteos' => [$partido->partido_id => 12],
            ]);
    }

    public function test_la_busqueda_de_persona_por_dni_devuelve_sus_datos_y_su_afiliacion(): void
    {
        $persona = $this->persona();
        $afiliado = $this->afiliar($persona);

        $this->actingAs($this->usuarioAdmin())
            ->getJson(route('votos.buscarPersona', ['dni' => $persona->dni]))
            ->assertOk()
            ->assertJson([
                'persona_id' => $persona->persona_id,
                'dni' => $persona->dni,
                'nombre_completo' => $persona->apellidoNombre(),
                'afiliacion' => $afiliado->descripcionAfiliacion(),
            ]);
    }

    public function test_la_busqueda_de_persona_sin_afiliacion_lo_informa(): void
    {
        $persona = $this->persona();

        $this->actingAs($this->usuarioAdmin())
            ->getJson(route('votos.buscarPersona', ['dni' => $persona->dni]))
            ->assertOk()
            ->assertJson(['afiliacion' => 'Sin afiliación']);
    }

    public function test_la_busqueda_de_persona_avisa_si_ya_es_personero(): void
    {
        $personero = $this->personero();

        $this->actingAs($this->usuarioAdmin())
            ->getJson(route('votos.buscarPersona', ['dni' => $personero->persona->dni]))
            ->assertStatus(409);
    }

    public function test_el_modal_de_personas_muestra_la_afiliacion_opcional(): void
    {
        $conAfiliacion = $this->persona('55555555');
        $this->afiliar($conAfiliacion);
        $this->persona('66666666');

        $respuesta = $this->actingAs($this->usuarioAdmin())
            ->getJson(route('votos.personasModal'))
            ->assertOk();

        $respuesta->assertJsonFragment(['dni' => '55555555']);
        $respuesta->assertJsonFragment(['dni' => '66666666']);
        $respuesta->assertJsonFragment(['afiliacion' => 'Sin afiliación']);
    }

    /*
    |--------------------------------------------------------------------------
    | Edición, eliminación y continuidad del flujo
    |--------------------------------------------------------------------------
    */

    public function test_el_formulario_de_edicion_muestra_mesa_y_conteo(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $voto = Voto::create([
            'mesa_id' => $personero->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 120,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.edit', $voto))
            ->assertOk()
            ->assertSee($partido->nombre)
            ->assertSee($personero->persona->apellidoNombre())
            ->assertSee('Mesa N° 1')
            ->assertSee('value="120"', false);
    }

    public function test_se_pueden_actualizar_los_votos(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $voto = Voto::create([
            'mesa_id' => $personero->mesa_id,
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
            'mesa_id' => $personero->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 250,
        ]);
    }

    public function test_se_puede_eliminar_un_registro_de_votos(): void
    {
        $personero = $this->personero();
        $partido = $this->partido();

        $voto = Voto::create([
            'mesa_id' => $personero->mesa_id,
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
            'mesa_id' => $personero->mesa_id,
            'partido_id' => $partido->partido_id,
            'votos' => 120,
        ]);
        $voto->delete();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('votos.registrar'), [
                'mesa_id' => $personero->mesa_id,
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

    public function test_al_registrar_una_persona_desde_el_conteo_se_vuelve_a_la_misma_mesa(): void
    {
        $mesa = $this->mesa();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('personas.store'), [
                'origen' => 'votos',
                'mesa_id' => $mesa->mesa_id,
                'dni' => '77777777',
                'nombres' => 'Luis',
                'primer_apellido' => 'Ramos',
            ])
            ->assertRedirect(route('votos.registrar', [
                'dni' => '77777777',
                'persona_id' => Persona::max('persona_id'),
                'mesa_id' => $mesa->mesa_id,
            ]));

        $this->assertDatabaseHas('personas', ['dni' => '77777777']);
    }

    public function test_el_formulario_retoma_la_mesa_y_la_persona_recien_creada(): void
    {
        $mesa = $this->mesa();
        $persona = $this->persona();

        $this->actingAs($this->usuarioAdmin())
            ->get(route('votos.registrar', [
                'mesa_id' => $mesa->mesa_id,
                'dni' => $persona->dni,
                'persona_id' => $persona->persona_id,
            ]))
            ->assertOk()
            ->assertSee($persona->apellidoNombre())
            ->assertSee('value="'.$persona->persona_id.'"', false);
    }

    public function test_un_personero_puede_registrar_una_persona_desde_el_conteo(): void
    {
        $personero = $this->personero();
        $usuario = $this->usuarioConAcceso($personero->persona, Menu::REGISTRAR_CONTEOS);

        // El formulario ofrece registrar la persona nueva con su DNI.
        $this->actingAs($usuario)
            ->get(route('personas.create', ['origen' => 'votos', 'dni' => '12345678']))
            ->assertOk()
            ->assertSee('name="origen" value="votos"', false);
    }
}
