<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class Usuario extends Authenticatable
{
    use HasFactory, SoftDeletes;

    /**
     * DNI de la persona genérica que opera como superadministrador del sistema.
     */
    public const DNI_ADMIN = '00000000';

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
    | Autenticación (guard de Laravel)
    |--------------------------------------------------------------------------
    */

    /**
     * Devuelve la contraseña usada por el guard de autenticación.
     * En esta tabla la contraseña vive en la columna `clave`, no en `password`.
     */
    public function getAuthPassword(): string
    {
        return $this->clave;
    }

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
        return $this->belongsToMany(Menu::class, 'accesos', 'usuario_id', 'menu_id', 'usuario_id', 'menu_id')
            ->withPivot('acceso_id', 'autor_id', 'editor_id')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Accesores
    |--------------------------------------------------------------------------
    */

    /**
     * Nombre a mostrar del usuario: toma el nombre completo de su persona.
     */
    public function getNombreAttribute(): ?string
    {
        return $this->persona?->apellidoNombre();
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
     * Indica si es el superadministrador del sistema (DNI de la persona genérica).
     */
    public function esAdmin(): bool
    {
        return $this->persona && $this->persona->dniNormalizado() === self::DNI_ADMIN;
    }

    /**
     * Asigna un menú al usuario de forma idempotente.
     *
     * @return Acceso
     */
    public function asignarMenu(int $menuId, ?int $editorId = null)
    {
        return $this->accesos()->updateOrCreate(
            [
                'usuario_id' => $this->usuario_id,
                'menu_id' => $menuId,
            ],
            [
                'editor_id' => $editorId,
            ]
        );
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
