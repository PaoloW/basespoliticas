<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Afiliado extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'afiliados';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'afiliado_id';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'afiliado_id',
        'persona_id',
        'base_id',
        'cargo_id',
        'autor_id',
        'editor_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * La persona afiliada.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'persona_id');
    }

    /**
     * La base a la que pertenece la afiliación.
     */
    public function base(): BelongsTo
    {
        return $this->belongsTo(Base::class, 'base_id', 'base_id');
    }

    /**
     * El cargo que ocupa el afiliado.
     */
    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cargo_id', 'cargo_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Descripción corta: "Persona — Base (Cargo)".
     */
    public function descripcionAfiliacion(): string
    {
        $base = $this->base?->descripcion ?? 'Sin base';
        $cargo = $this->cargo?->descripcion ?? 'Sin cargo';

        return $base.' ('.$cargo.')';
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
            'afiliado_id' => $this->afiliado_id,
            'persona_id' => $this->persona_id,
            'base_id' => $this->base_id,
            'cargo_id' => $this->cargo_id,
            'persona' => $this->persona?->toRepresentacion(),
            'base' => $this->base?->toRepresentacion(),
            'cargo' => $this->cargo?->toRepresentacion(),
            'afiliacion' => $this->descripcionAfiliacion(),
        ];
    }
}
