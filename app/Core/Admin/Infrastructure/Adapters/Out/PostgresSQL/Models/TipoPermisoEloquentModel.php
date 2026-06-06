<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TipoPermisoEloquentModel
 *
 * Modelo Eloquent para la tabla tb_cat_tipo_permiso.
 * Preferentemente usar el Repository con Query Builder.
 */
final class TipoPermisoEloquentModel extends Model
{
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'tb_cat_tipo_permiso';

    /**
     * Clave primaria de la tabla
     */
    protected $primaryKey = 'id_nu_tipo_permiso';

    /**
     * Indica si el modelo debe usar timestamps
     */
    public $timestamps = true;

    /**
     * Atributos asignables en masa
     */
    protected $fillable = [
        'ln_nombre',
        'ind_activo',
        'sn_descripcion',
    ];

    /**
     * Conversión de tipos de atributos
     */
    protected $casts = [
        'id_nu_tipo_permiso' => 'integer',
        'ln_nombre' => 'string',
        'ind_activo' => 'boolean',
        'sn_descripcion' => 'string',
    ];
}
