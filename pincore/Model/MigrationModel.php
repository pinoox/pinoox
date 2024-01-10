<?php
/**
 *
 *
 */

namespace Pinoox\Model;

use Pinoox\Component\Database\Model;


class MigrationModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pincore_migration';

    public $timestamps = false;

    /**
     * @param string[] $fillable
     */
    protected $fillable = ['migration', 'batch', 'app'];

}
