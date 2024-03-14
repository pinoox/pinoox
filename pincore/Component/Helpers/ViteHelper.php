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


namespace Pinoox\Component\Helpers;


use Pinoox\Portal\View;

class ViteHelper
{
    protected string $fileManifest;
    protected string $mainDirectory;
    protected string $themePath;
    protected array $outputBuffer = [];

    public function __construct(string $themePath, string $fileManifest = 'dist/.vite/manifest.json')
    {
        $this->fileManifest = $fileManifest;
        $this->themePath = $themePath;
        $this->mainDirectory = $this->findMainDirectory($fileManifest);
    }

    protected function findMainDirectory(string $fileManifest): string
    {
        $mainDirectory = explode('/', dirname($fileManifest));
        return !empty($mainDirectory) ? $mainDirectory[0] : '/';
    }

    public function vite(string $name, ?string $fileManifest = null): array
    {
        $fileManifest = $fileManifest ?? $this->fileManifest;
        $manifest = $this->loadManifest($fileManifest);

        $mainDirectory = !empty($fileManifest) ? $this->findMainDirectory($fileManifest) : $this->mainDirectory;
        if (!empty($manifest[$name])) {
            $this->processFile($manifest[$name], $mainDirectory);
        }

        return $this->outputBuffer;
    }

    public function printVite(string $name, ?string $fileManifest = null): void
    {
        $output = $this->vite($name, $fileManifest);
        $this->printOutputBuffer($output);
    }

    protected function loadManifest(string $fileManifest): array
    {
        $pathManifest = $this->themePath . '/' . $fileManifest;
        if (is_file($pathManifest)) {
            $manifest = file_get_contents($pathManifest);
            return json_decode($manifest, true) ?: [];
        }
        return [];
    }

    protected function processFile(array $fileData, string $dir): void
    {
        if (!empty($fileData['file'])) {
            $this->addFile($fileData['file'], $dir);
        }

        if (!empty($fileData['css'])) {
            foreach ($fileData['css'] as $css) {
                $this->addFile($css, $dir);
            }
        }
    }

    protected function addFile(string $fileName, string $dir): void
    {
        $url = assets($dir . '/' . $fileName);
        $this->outputBuffer[] = match (pathinfo($fileName, PATHINFO_EXTENSION)) {
            'js' => '<script type="module"  src="' . $url . '"></script>',
            'css' => '<link rel="stylesheet" href="' . $url . '">',
            default => $url,
        };
    }

    protected function printOutputBuffer(array $output): void
    {
        foreach ($output as $index => $item) {
            if ($index === 0)
                echo $item;
            else
                echo "\n\t" . $item;

        }
    }

    public static function useVite(string $name, ?string $fileManifest = 'dist/.vite/manifest.json'): array
    {
        $viteHelper = new self(View::path()->assets());
        return $viteHelper->vite($name, $fileManifest);
    }

    public static function usePrintVite(string $name, ?string $fileManifest = 'dist/.vite/manifest.json'): void
    {
        $viteHelper = new self(View::path()->assets());
        $viteHelper->printVite($name, $fileManifest);
    }
}
