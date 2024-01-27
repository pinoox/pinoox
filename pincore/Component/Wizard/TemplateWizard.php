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

namespace Pinoox\Component\Wizard;

use PhpZip\Exception\ZipException;
use Pinoox\Component\Kernel\Exception;

class TemplateWizard extends Wizard implements WizardInterface
{
    protected string $type = 'template';

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
        return $this->packagePath . '/theme/' . $this->info['name'] . '/';
    }
}