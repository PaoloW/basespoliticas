<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class Usuario extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'usuario_id';

    /**
     * Atributos asignables en masa. La clave siempre se debe setear
     * mediante Hash::make, por eso no debe ir en fillable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'usuario_id',
        'persona_id',
        'autor_id',
        'editor_id',
    ];

    /**
     * Atributos ocultos en la serialización.
     *
     * @var list<string>
     */
    protected $hidden = [
        'clave',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * La persona a la que pertenece la cuenta.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'persona_id');
    }

    /**
     * Accesos (asignaciones) de menús para este usuario.
     */
    public function accesos(): HasMany
    {
        return $this->hasMany(Acceso::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Los menús a los que tiene acceso el usuario (a través de accesos).
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'accesos', 'usuario_id', 'menu_id', 'usuario_id', 'menu_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si la clave recibida coincide con el hash almacenado.
     */
    public function verificarClave(?string $clave): bool
    {
        if (empty($this->clave) || empty($clave)) {
            return false;
        }

        return Hash::check($clave, $this->clave);
    }

    /**
     * Indica si el usuario tiene asignado el menú solicitado.
     */
    public function tieneAcceso(int $menuId): bool
    {
        return $this->accesos()->where('menu_id', $menuId)->exists();
    }

    /**
     * Asigna un menú al usuario de forma idempotente.
     */
    public function asignarMenu(int $menuId, ?int $autorId = null): void
    {
        $this->accesos()->firstOrCreate([
            'usuario_id' => $this->usuario_id,
            'menu_id' => $menuId,
        ], [
            'autor_id' => $autorId,
            'editor_id' => $autorId,
        ]);
    }

    /**
     * Convierte el modelo a un arreglo limpio para el frontend.
     */
    public function toRepresentacion(): array
    {
        return [
            'usuario_id' => $this->usuario_id,
            'persona_id' => $this->persona_id,
            'persona' => $this->persona?->toRepresentacion(),
            'menus' => $this->menus()->pluck('menu_id')->toArray(),
        ];
    }
}
