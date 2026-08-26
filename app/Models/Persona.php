<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Persona extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'personas';

    /**
     * Clave primaria personalizada. En esta tabla el id se asigna de forma manual,
     * por lo que no es autoincremental.
     *
     * @var string
     */
    protected $primaryKey = 'persona_id';

    /**
     * Indica si la clave primaria es autoincremental.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * El tipo de la clave primaria es entero.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'dni',
        'nombres',
        'primer_apellido',
        'segundo_apellido',
        'codigo_mesa',
        'fecha_nacimiento',
        'telefono',
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
            'fecha_nacimiento' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * La cuenta de usuario asociada a la persona.
     */
    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class, 'persona_id', 'persona_id');
    }

    /**
     * Las afiliaciones de la persona a distintas bases y cargos.
     */
    public function afiliados(): HasMany
    {
        return $this->hasMany(Afiliado::class, 'persona_id', 'persona_id');
    }

    /**
     * Los registros donde la persona actúa como personero de mesa.
     */
    public function personeros(): HasMany
    {
        return $this->hasMany(Personero::class, 'persona_id', 'persona_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Conversión y formateo de datos
    |--------------------------------------------------------------------------
    */

    /**
     * Devuelve el nombre completo de la persona (nombres + apellidos).
     */
    public function nombreCompleto(): string
    {
        return trim(implode(' ', array_filter([
            $this->nombres,
            $this->primer_apellido,
            $this->segundo_apellido,
        ])));
    }

    /**
     * Devuelve solo los apellidos de la persona.
     */
    public function apellidos(): string
    {
        return trim(implode(' ', array_filter([
            $this->primer_apellido,
            $this->segundo_apellido,
        ])));
    }

    /**
     * Devuelve el DNI limpio (solo dígitos), o null si no existe.
     */
    public function dniNormalizado(): ?string
    {
        if (empty($this->dni)) {
            return null;
        }

        return preg_replace('/\D/', '', $this->dni);
    }

    /**
     * Fecha de nacimiento formateada como dd/mm/yyyy o null.
     */
    public function fechaNacimientoFormateada(): ?string
    {
        return $this->fecha_nacimiento?->format('d/m/Y');
    }

    /**
     * Encabezado corto usado en listados: "apellidos, nombres".
     */
    public function apellidoNombre(): string
    {
        $nombre = implode(' ', array_filter([$this->nombres ?? null]));
        $apellidos = $this->apellidos();

        if ($apellidos === '') {
            return $nombre;
        }

        return trim($apellidos.', '.$nombre);
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones de negocio
    |--------------------------------------------------------------------------
    */

    /**
     * Indica si la persona tiene al menos una afiliación activa.
     */
    public function esAfiliado(): bool
    {
        return $this->afiliados()->exists();
    }

    /**
     * Indica si la persona es(personero de al menos una mesa.
     */
    public function esPersonero(): bool
    {
        return $this->personeros()->exists();
    }

    /**
     * Indica si la persona posee una cuenta de usuario del sistema.
     */
    public function esUsuario(): bool
    {
        return $this->usuario()->exists();
    }

    /**
     * Convierte el modelo a un arreglo limpio para consumir en el frontend.
     */
    public function toRepresentacion(): array
    {
        return [
            'persona_id' => $this->persona_id,
            'dni' => $this->dni,
            'nombres' => $this->nombres,
            'primer_apellido' => $this->primer_apellido,
            'segundo_apellido' => $this->segundo_apellido,
            'nombre_completo' => $this->nombreCompleto(),
            'apellido_nombre' => $this->apellidoNombre(),
            'codigo_mesa' => $this->codigo_mesa,
            'fecha_nacimiento' => $this->fechaNacimientoFormateada(),
            'telefono' => $this->telefono,
        ];
    }
}
