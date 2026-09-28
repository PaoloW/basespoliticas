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
        'partido_id',
        'persona_id',
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
     * El partido cuyos votos se registran.
     */
    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class, 'partido_id', 'partido_id');
    }

    /**
     * La persona (personero) que reporta los votos.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'persona_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Código de mesa de la persona del registro.
     */
    public function codigoMesa(): ?string
    {
        return $this->persona?->codigo_mesa;
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
            'partido_id' => $this->partido_id,
            'persona_id' => $this->persona_id,
            'partido' => $this->partido?->toRepresentacion(),
            'persona' => $this->persona?->toRepresentacion(),
            'codigo_mesa' => $this->codigoMesa(),
            'votos' => $this->votos ?? 0,
        ];
    }
}
