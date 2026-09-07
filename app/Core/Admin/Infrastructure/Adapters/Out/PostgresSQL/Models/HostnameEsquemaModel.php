<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Eloquent Model for tb_r_hostname_esquema table (pivot)
 *
 * Maps PostgreSQL table to PHP object. Lives in Infrastructure layer.
 *
 * Internal to EsquemaRepository only — never exposed via its own Port/OutAdapter,
 * since no inverse hostnames-by-esquema endpoint exists in this story.
 *
 * @property int $id_nu_hostname_esquema Primary key
 * @property int $id_nu_hostname Hostname asociado
 * @property int $id_nu_esquema Esquema asociado
 * @property int $ind_activo Indicador de asociación activa (0=inactivo, 1=activo)
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class HostnameEsquemaModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'tb_r_hostname_esquema';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id_nu_hostname_esquema';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_nu_hostname',
        'id_nu_esquema',
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
            'id_nu_hostname' => 'integer',
            'id_nu_esquema' => 'integer',
            'ind_activo' => 'integer',
        ];
    }
}
