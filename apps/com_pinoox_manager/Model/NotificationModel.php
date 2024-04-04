<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */


namespace App\com_pinoox_manager\Model;


use Pinoox\Component\Database\Model;

class NotificationModel extends Model
{
    protected $table = 'com_pinoox_manager_notification';

    protected $primaryKey = 'ntf_id';
    public $timestamps = true;

    protected $fillable = [
        'app',
        'title',
        'message',
        'action_key',
        'action_data',
        'push_date',
        'status',
    ];

    protected $casts = [
        'push_date' => 'datetime',
        'action_data' => 'json',
    ];

    protected $enumStatus = ['pending', 'send', 'seen', 'hide'];

    protected function setStatusAttribute($value)
    {
        if (!in_array($value, $this->enumStatus)) {
            throw new \InvalidArgumentException("The status must be one of: " . implode(', ', $this->enumStatus));
        }
        $this->attributes['status'] = $value;
    }
}