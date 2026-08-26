<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Centro extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'centros';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'centro_id';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'centro_id',
        'descripcion',
        'ubicacion',
        'distrito',
        'autor_id',
        'editor_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Mesas de votación que pertenecen a este centro.
     */
    public function mesas(): HasMany
    {
        return $this->hasMany(Mesa::class, 'centro_id', 'centro_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Cantidad de mesas que tiene el centro.
     */
    public function totalMesas(): int
    {
        return $this->mesas()->count();
    }

    /**
     * Suma total de votantes potenciales en las mesas del centro.
     */
    public function totalVotantes(): int
    {
        return (int) $this->mesas()->sum('votantes');
    }

    /*
    |--------------------------------------------------------------------------
    | Conversión de datos
    |--------------------------------------------------------------------------
    */

    /**
     * Convierte el modelo a un arreglo limpio para el frontend.
     */
    public function toRepresentacion(): array
    {
        return [
            'centro_id' => $this->centro_id,
            'descripcion' => $this->descripcion,
            'ubicacion' => $this->ubicacion,
            'distrito' => $this->distrito,
            'total_mesas' => $this->totalMesas(),
            'total_votantes' => $this->totalVotantes(),
        ];
    }
}
