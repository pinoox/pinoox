<?php
/**
 *
 *
 */

namespace Pinoox\Model;

use Pinoox\Component\Database\Model;


class HistoryModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = Table::HISTORY;

    public $timestamps = false;

    /**
     * @param string[] $fillable
     */
    protected $fillable = [
        'type',
        'migration',
        'batch',
        'app',
        'status',
        'checksum',
        'duration_ms',
        'executed_at',
        'error',
        'metadata',
    ];
}
