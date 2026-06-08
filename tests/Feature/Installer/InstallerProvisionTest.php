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

use Pinoox\Component\Package\AppDependency;
use Pinoox\Component\Package\AppProvisioner;

it('documents installer provisioning steps mirrored from pinx install', function () {
    $provisioner = new ReflectionClass(AppProvisioner::class);
    $dependency = new ReflectionClass(AppDependency::class);

    expect($provisioner->hasMethod('provisionCore'))->toBeTrue()
        ->and($provisioner->hasMethod('provisionInstalledApps'))->toBeTrue()
        ->and($dependency->hasMethod('sortForInstall'))->toBeTrue();
});
