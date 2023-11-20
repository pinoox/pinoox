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

namespace pinoox\component\wizard;

use PhpZip\Exception\ZipException;
use pinoox\component\kernel\Exception;

class TemplateWizard extends Wizard implements WizardInterface
{

    public function __construct()
    {
        $this->type('template');
    }

    public function type(string $type)
    {
        $this->type = $type;
    }

    public function open(string $path): Wizard
    {
        parent::open($path);

        //extract target file (meta.json)
        $this->extractTemp($this->targetFile());
        $this->loadTargetFileFromPin();

        return $this;
    }


    /**
     * @throws ZipException
     */
    public function install(): array
    {
        $zip = $this->extract($this->getThemePath());
        return [
            'message' => 'Template was installed successfully',
            'listFiles' => $zip->getListFiles(),
        ];
    }


    public function getInfo(): array|null
    {
        return $this->info;
    }

    public function isUpdateAvailable(): bool
    {
        // TODO: Implement isUpdateAvailable() method.
        return false;
    }

    private function getThemePath(): string
    {
        return $this->packagePath . 'theme' . DS . $this->info['name'] . DS;
    }
}