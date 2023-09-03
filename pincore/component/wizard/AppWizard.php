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
use pinoox\component\migration\Migrator;

class AppWizard extends Wizard implements WizardInterface
{

    /**
     * @var bool Indicates whether migration is needed during installation.
     */
    private bool $migration = false;

    public function __construct()
    {
        $this->type('app');
    }

    /**
     * Set the type of package ( app or template)
     *
     * @param string $type The type of the wizard.
     */
    public function type(string $type)
    {
        $this->type = $type;
    }

    /**
     * Install the package and return the installation result.
     *
     * @return array|bool An array containing the installation result, or false if installation fails.
     * @throws Exception If any other error occurs during installation.
     *
     * @throws ZipException If there is an issue with the ZIP file.
     */
    public function install(): array|bool
    {
        if ($this->isInstalled() && !$this->force) {
            $this->setError('The package is already installed');
            return false;
        }

        $zip = $this->extract($this->packagePath);

        if ($this->migration) {
            $this->migrate();
        }

        return [
            'message' => 'The package was installed successfully',
            'listFiles' => $zip->getListFiles(),
        ];
    }

    /**
     * Open the package file and extract necessary information from it.
     *
     * @param string $path The path to the package file.
     *
     * @return Wizard The current instance of the wizard.
     * @throws Exception
     */
    public function open(string $path): Wizard
    {
        parent::open($path);

        //extract target file (app.php)
        $this->extractTemp($this->targetFile());
        $this->loadTargetFileFromPin();

        //extract icon
        $this->extractTemp($this->getIconPath());
        $this->addIcon();

        return $this;
    }

    /**
     * Add the icon path to the package information.
     */
    private function addIcon(): void
    {
        if (!isset($this->info)) return;
        $this->info['icon_path'] = $this->tmpPathPackage . DS . $this->info['icon'];
    }

    /**
     * Get the icon path from the package information.
     *
     * @return string|null The icon path if available, null otherwise.
     */
    private function getIconPath(): ?string
    {
        return $this->info['icon'] ?? null;
    }

    /**
     * Check if an update is available for the package.
     *
     * @return bool Returns true if an update is available, false otherwise.
     * @throws Exception If there is an issue with the package information.
     *
     */
    public function isUpdateAvailable(): bool
    {
        if (!$this->isUpdate) return false;

        $existsInfo = $this->getExistsPackageInfo();
        return $existsInfo['version-code'] <= $this->getInfo()['version-code'];
    }

    /**
     * Check if the package is installed.
     *
     * @return bool Returns true if the package is installed, false otherwise.
     */
    public function isInstalled(): bool
    {
        return is_dir(PINOOX_APP_PATH . $this->package);
    }

    /**
     * Enable migration during the installation process.
     */
    public function migration($val = true): Wizard
    {
        $this->migration = $val;
        return $this;
    }

    /**
     * Perform the migration process.
     * @throws Exception|\Exception
     */
    private function migrate(): void
    {
        $migrator = new Migrator($this->package);
        $migrator->run();
    }

    /**
     * Get the package information.
     *
     * @return array|null The package information if available, null otherwise.
     */
    public function getInfo(): array|null
    {
        return $this->info;
    }
}