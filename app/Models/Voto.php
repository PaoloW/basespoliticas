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
        'mesa_id',
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
     * La mesa cuyo conteo se registra.
     */
    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'mesa_id', 'mesa_id');
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
     * Etiqueta de la mesa del conteo.
     */
    public function codigoMesa(): ?string
    {
        return $this->mesa?->etiqueta();
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
            'mesa_id' => $this->mesa_id,
            'partido_id' => $this->partido_id,
            'mesa' => $this->mesa?->toRepresentacion(),
            'partido' => $this->partido?->toRepresentacion(),
            'codigo_mesa' => $this->codigoMesa(),
            'votos' => $this->votos ?? 0,
        ];
    }
}
