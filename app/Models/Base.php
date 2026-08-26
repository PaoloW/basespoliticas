<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Base extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'bases';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'base_id';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'base_id',
        'descripcion',
        'ubicacion',
        'autor_id',
        'editor_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Afiliados registrados en esta base.
     */
    public function afiliados(): HasMany
    {
        return $this->hasMany(Afiliado::class, 'base_id', 'base_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Cantidad de afiliados activos de la base.
     */
    public function totalAfiliados(): int
    {
        return $this->afiliados()->count();
    }

    /**
     * Nombre de la base con su ubicación si existe.
     */
    public function descripcionConUbicacion(): string
    {
        if (empty($this->ubicacion)) {
            return $this->descripcion;
        }

        return $this->descripcion.' — '.$this->ubicacion;
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
            'base_id' => $this->base_id,
            'descripcion' => $this->descripcion,
            'ubicacion' => $this->ubicacion,
            'afiliados' => $this->totalAfiliados(),
        ];
    }
}
