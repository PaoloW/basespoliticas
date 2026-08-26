<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Acceso extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'accesos';

    /**
     * Clave primaria personalizada.
     *
     * @var string
     */
    protected $primaryKey = 'acceso_id';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'acceso_id',
        'usuario_id',
        'menu_id',
        'autor_id',
        'editor_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * El usuario al que se le concede el acceso.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }

    /**
     * El menú concedido.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'menu_id');
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
            'acceso_id' => $this->acceso_id,
            'usuario_id' => $this->usuario_id,
            'menu_id' => $this->menu_id,
            'usuario' => $this->usuario ? [
                'usuario_id' => $this->usuario->usuario_id,
                'persona_id' => $this->usuario->persona_id,
            ] : null,
            'menu' => $this->menu ? [
                'menu_id' => $this->menu->menu_id,
                'descripcion' => $this->menu->descripcion,
            ] : null,
        ];
    }
}
