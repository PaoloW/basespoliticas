<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personero extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'personeros';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'personero_id';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'personero_id',
        'persona_id',
        'mesa_id',
        'conteo',
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
            'conteo' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * La persona que actúa como personero.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'persona_id');
    }

    /**
     * La mesa a la que está asignado el personero.
     */
    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'mesa_id', 'mesa_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Registra el conteo de votos, sobrescribiendo el valor anterior.
     */
    public function registrarConteo(int $conteo, ?int $editorId = null): self
    {
        $this->update([
            'conteo' => max(0, $conteo),
            'editor_id' => $editorId ?? $this->editor_id,
        ]);

        return $this;
    }

    /**
     * Incrementa (o reduce, con negativos) el conteo acumulado de votos.
     */
    public function incrementarConteo(int $cantidad, ?int $editorId = null): self
    {
        $nuevo = max(0, ($this->conteo ?? 0) + $cantidad);
        $this->update([
            'conteo' => $nuevo,
            'editor_id' => $editorId ?? $this->editor_id,
        ]);

        return $this;
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
            'personero_id' => $this->personero_id,
            'persona_id' => $this->persona_id,
            'mesa_id' => $this->mesa_id,
            'persona' => $this->persona?->toRepresentacion(),
            'mesa' => $this->mesa?->toRepresentacion(),
            'conteo' => $this->conteo ?? 0,
        ];
    }
}
