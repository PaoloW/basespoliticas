<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo. La tabla no es "menus" sino "menu".
     *
     * @var string
     */
    protected $table = 'menu';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'menu_id';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'menu_id',
        'menu_padre_id',
        'descripcion',
        'icono',
        'autor_id',
        'editor_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones (jerarquía de menús)
    |--------------------------------------------------------------------------
    */

    /**
     * El menú padre del que cuelga este submenú.
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_padre_id', 'menu_id');
    }

    /**
     * Los submenús que dependen de este menú.
     */
    public function hijos(): HasMany
    {
        return $this->hasMany(Menu::class, 'menu_padre_id', 'menu_id');
    }

    /**
     * Los accesos (asignaciones) que apuntan a este menú.
     */
    public function accesos(): HasMany
    {
        return $this->hasMany(Acceso::class, 'menu_id', 'menu_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Indica si el menú es raíz (no tiene padre).
     */
    public function esPrincipal(): bool
    {
        return $this->menu_padre_id === null;
    }

    /**
     * Indica si el menú posee submenús.
     */
    public function tieneHijos(): bool
    {
        return $this->hijos()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Conversión de datos
    |--------------------------------------------------------------------------
    */

    /**
     * Convierte el modelo a un arreglo limpio para el frontend,
     * incluyendo opcionalmente los submenús (hijos).
     */
    public function toRepresentacion(bool $conHijos = false): array
    {
        return [
            'menu_id' => $this->menu_id,
            'menu_padre_id' => $this->menu_padre_id,
            'descripcion' => $this->descripcion,
            'icono' => $this->icono,
            'hijos' => $conHijos ? $this->hijos()->get()->map(fn (Menu $hijo) => $hijo->toRepresentacion())->values()->all() : [],
        ];
    }
}
