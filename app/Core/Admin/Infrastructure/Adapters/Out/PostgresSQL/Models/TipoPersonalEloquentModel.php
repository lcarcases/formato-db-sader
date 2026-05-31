<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * TipoPersonalEloquentModel
 *
 * Eloquent model for tb_cat_tipo_personal table.
 * Located in Infrastructure layer - Laravel-specific code.
 *
 * ⚠️ NOTE: This model is NOT a Domain Entity!
 * - Domain Entity: Pure PHP in Domain layer with business logic
 * - Eloquent Model: Infrastructure concern for database persistence
 *
 * Pattern: Repository uses this model to query database, then maps to Domain Entity or DTO
 *
 * @property int $id_nu_tipo_personal
 * @property string $sn_nombre
 * @property string|null $sn_descripcion
 * @property bool $ind_activo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class TipoPersonalEloquentModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tb_cat_tipo_personal';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id_nu_tipo_personal';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sn_nombre',
        'sn_descripcion',
        'ind_activo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ind_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Scope a query to only include active tipos personal.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('ind_activo', true);
    }

    /**
     * Scope a query to order by nombre ascending.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrderedByNombre($query)
    {
        return $query->orderBy('sn_nombre', 'asc');
    }
}
