<?php

namespace Tests\Feature;

use App\Models\Afiliado;
use App\Models\Base;
use App\Models\Cargo;
use App\Models\Centro;
use App\Models\Mesa;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnicidadTest extends TestCase
{
    use RefreshDatabase;

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

    /*
    |--------------------------------------------------------------------------
    | Centro de votación
    |--------------------------------------------------------------------------
    */

    public function test_no_se_puede_registrar_un_centro_con_la_misma_descripcion(): void
    {
        Centro::create(['descripcion' => 'Colegio San Jose']);

        $this->actingAs($this->usuarioAdmin())
            ->from(route('centros.create'))
            ->post(route('centros.store'), ['descripcion' => 'Colegio San Jose'])
            ->assertSessionHasErrors('descripcion');

        $this->assertDatabaseCount('centros', 1);
    }

    public function test_si_se_puede_reutilizar_la_descripcion_de_un_centro_eliminado(): void
    {
        $centro = Centro::create(['descripcion' => 'Colegio San Jose']);
        $centro->delete();

        $this->actingAs($this->usuarioAdmin())
            ->post(route('centros.store'), ['descripcion' => 'Colegio San Jose'])
            ->assertRedirect(route('centros.index'));

        // La fila eliminada no bloquea el nuevo registro (solo una vigente).
        $this->assertSame(1, Centro::whereNull('deleted_at')->count());
        $this->assertDatabaseHas('centros', ['descripcion' => 'Colegio San Jose', 'deleted_at' => null]);
    }

    /*
    |--------------------------------------------------------------------------
    | Base
    |--------------------------------------------------------------------------
    */

    public function test_no_se_puede_registrar_una_base_con_la_misma_descripcion(): void
    {
        Base::create(['descripcion' => 'Base Norte']);

        $this->actingAs($this->usuarioAdmin())
            ->from(route('bases.create'))
            ->post(route('bases.store'), ['descripcion' => 'Base Norte'])
            ->assertSessionHasErrors('descripcion');

        $this->assertDatabaseCount('bases', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Mesa de votación
    |--------------------------------------------------------------------------
    */

    public function test_no_se_puede_registrar_una_mesa_repetida_en_el_mismo_centro(): void
    {
        $centro = Centro::create(['descripcion' => 'Colegio San Jose']);
        Mesa::create(['descripcion' => 1, 'centro_id' => $centro->centro_id]);

        $this->actingAs($this->usuarioAdmin())
            ->from(route('mesas.create'))
            ->post(route('mesas.store'), [
                'descripcion' => 1,
                'centro_id' => $centro->centro_id,
            ])
            ->assertSessionHasErrors('descripcion');

        $this->assertDatabaseCount('mesas', 1);
    }

    public function test_una_mesa_puede_repetir_su_numero_en_otro_centro(): void
    {
        $centroA = Centro::create(['descripcion' => 'Colegio San Jose']);
        $centroB = Centro::create(['descripcion' => 'Colegio Los Andes']);

        $this->actingAs($this->usuarioAdmin())
            ->post(route('mesas.store'), [
                'descripcion' => 1,
                'centro_id' => $centroA->centro_id,
            ])
            ->assertRedirect(route('mesas.index'));

        $this->actingAs($this->usuarioAdmin())
            ->post(route('mesas.store'), [
                'descripcion' => 1,
                'centro_id' => $centroB->centro_id,
            ])
            ->assertRedirect(route('mesas.index'));

        $this->assertDatabaseCount('mesas', 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Afiliado
    |--------------------------------------------------------------------------
    */

    public function test_no_se_puede_afiliar_dos_veces_a_la_misma_persona(): void
    {
        $persona = $this->persona();
        $base = Base::create(['descripcion' => 'Base Norte']);
        $cargo = Cargo::create(['descripcion' => 'Vocal']);

        Afiliado::create([
            'persona_id' => $persona->persona_id,
            'base_id' => $base->base_id,
            'cargo_id' => $cargo->cargo_id,
        ]);

        $this->actingAs($this->usuarioAdmin())
            ->from(route('afiliados.create'))
            ->post(route('afiliados.store'), [
                'persona_id' => $persona->persona_id,
                'base_id' => $base->base_id,
                'cargo_id' => $cargo->cargo_id,
            ])
            ->assertSessionHasErrors('persona_id');

        $this->assertDatabaseCount('afiliados', 1);
    }

    public function test_la_base_de_datos_impide_duplicar_la_persona_afiliada(): void
    {
        $persona = $this->persona();
        $base = Base::create(['descripcion' => 'Base Norte']);
        $cargo = Cargo::create(['descripcion' => 'Vocal']);

        $datos = [
            'persona_id' => $persona->persona_id,
            'base_id' => $base->base_id,
            'cargo_id' => $cargo->cargo_id,
        ];

        Afiliado::create($datos);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Afiliado::create($datos);
    }
}
