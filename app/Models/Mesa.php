<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mesa extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'mesas';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'mesa_id';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'mesa_id',
        'descripcion',
        'centro_id',
        'votantes',
        'autor_id',
        'editor_id',
    ];

    /**
     * Conversión (casting) de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'descripcion' => 'integer',
            'votantes' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * El centro de votación al que pertenece la mesa.
     */
    public function centro(): BelongsTo
    {
        return $this->belongsTo(Centro::class, 'centro_id', 'centro_id');
    }

    /**
     * Los personeros asignados a la mesa.
     */
    public function personeros(): HasMany
    {
        return $this->hasMany(Personero::class, 'mesa_id', 'mesa_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Cantidad de personeros registrados en la mesa.
     */
    public function totalPersoneros(): int
    {
        return $this->personeros()->count();
    }

    /**
     * Etiqueta legible de la mesa: "Mesa N° {descripcion}".
     */
    public function etiqueta(): string
    {
        if ($this->descripcion === null) {
            return 'Mesa '.$this->mesa_id;
        }

        return 'Mesa N° '.$this->descripcion;
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
            'mesa_id' => $this->mesa_id,
            'descripcion' => $this->descripcion,
            'etiqueta' => $this->etiqueta(),
            'centro_id' => $this->centro_id,
            'centro' => $this->centro?->toRepresentacion(),
            'votantes' => $this->votantes,
            'personeros' => $this->totalPersoneros(),
        ];
    }
}
