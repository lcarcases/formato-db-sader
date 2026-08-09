<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Eloquent Model for tb_cat_base_datos table
 *
 * Maps PostgreSQL table to PHP object. Lives in Infrastructure layer.
 *
 * @property int $id_nu_base_datos Primary key
 * @property string $sn_nombre Nombre/código de la base de datos
 * @property int $ind_activo Indicador activo (0=inactivo, 1=activo)
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class BaseDatosModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'tb_cat_base_datos';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id_nu_base_datos';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sn_nombre',
        'ind_activo',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ind_activo' => 'integer',
        ];
    }
}
