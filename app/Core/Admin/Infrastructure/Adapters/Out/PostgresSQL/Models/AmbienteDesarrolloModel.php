<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Eloquent Model for tb_cat_ambiente_desarrollo table
 *
 * Maps PostgreSQL table to PHP object. Lives in Infrastructure layer.
 *
 * @property int $id_nu_ambiente_desarrollo Primary key
 * @property string $sn_nombre Nombre del ambiente
 * @property int $ind_activo Indicador activo (0=inactivo, 1=activo)
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class AmbienteDesarrolloModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'tb_cat_ambiente_desarrollo';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id_nu_ambiente_desarrollo';

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
