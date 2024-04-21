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


namespace Pinoox\Component\Database;

use Attribute;

/**
 * @Attribute
 * @interface Transactional
 * @target {class,method}
 * @description Enables transactional behavior for a class or method.
 *
 * When applied to a class, all methods of the class will be wrapped in a transaction.
 * When applied to a method, only that method will be wrapped in a transaction.
 */
#[Attribute]
interface Transactional
{

}