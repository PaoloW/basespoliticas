<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voto extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'votos';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'voto_id';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'voto_id',
        'personero_id',
        'partido_id',
        'votos',
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
            'votos' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * El personero que reporta el conteo.
     */
    public function personero(): BelongsTo
    {
        return $this->belongsTo(Personero::class, 'personero_id', 'personero_id');
    }

    /**
     * El partido cuyos votos se registran.
     */
    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class, 'partido_id', 'partido_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Etiqueta de la mesa del personero que reportó los votos.
     */
    public function codigoMesa(): ?string
    {
        return $this->personero?->mesa?->etiqueta();
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
            'voto_id' => $this->voto_id,
            'personero_id' => $this->personero_id,
            'partido_id' => $this->partido_id,
            'personero' => $this->personero?->toRepresentacion(),
            'partido' => $this->partido?->toRepresentacion(),
            'codigo_mesa' => $this->codigoMesa(),
            'votos' => $this->votos ?? 0,
        ];
    }
}
