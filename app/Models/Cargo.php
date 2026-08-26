<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cargo extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'cargos';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'cargo_id';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cargo_id',
        'descripcion',
        'autor_id',
        'editor_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Afiliados que ocupan este cargo.
     */
    public function afiliados(): HasMany
    {
        return $this->hasMany(Afiliado::class, 'cargo_id', 'cargo_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Cantidad de afiliados que ocupan este cargo.
     */
    public function totalAfiliados(): int
    {
        return $this->afiliados()->count();
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
            'cargo_id' => $this->cargo_id,
            'descripcion' => $this->descripcion,
            'afiliados' => $this->totalAfiliados(),
        ];
    }
}
