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
    protected array $processedFiles = [];
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
        $this->outputBuffer = [];
        $fileManifest = $fileManifest ?? $this->fileManifest;
        $manifest = $this->loadManifest($fileManifest);

        $mainDirectory = !empty($fileManifest) ? $this->findMainDirectory($fileManifest) : $this->mainDirectory;
        
        if (!empty($manifest[$name])) {
            $this->processFile($manifest[$name], $manifest, $mainDirectory);
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

    protected function processFile(array $fileData, array $manifest, string $dir, array $processed = []): void
    {
        // Process imports first (JS chunk dependencies, including Vite 8 rolldown-runtime)
        if (!empty($fileData['imports'])) {
            foreach ($fileData['imports'] as $importKey) {
                if (empty($processed[$importKey]) && !empty($manifest[$importKey])) {
                    $processed[$importKey] = true;
                    $this->processFile($manifest[$importKey], $manifest, $dir, $processed);
                }
            }
        }

        // Add main file
        if (!empty($fileData['file'])) {
            $this->addFile($fileData['file'], $dir);
        }

        // Add CSS files (after main file to ensure proper order)
        if (!empty($fileData['css'])) {
            foreach ($fileData['css'] as $css) {
                $this->addFile($css, $dir);
            }
        }

        // Vite 8+ lists static assets (fonts, images) on the entry; they are loaded via CSS/JS URLs.
        // No extra HTML tags are emitted here.
    }

    protected function addFile(string $fileName, string $dir): void
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        // Only process JS and CSS files
        if (!in_array($extension, ['js', 'css'])) {
            return;
        }

        $url = assets($dir . '/' . $fileName);
        $this->outputBuffer[] = match ($extension) {
            'js' => '<script type="module" src="' . $url . '"></script>',
            'css' => '<link rel="stylesheet" href="' . $url . '"/>',
            default => $url,
        };
    }

    protected function printOutputBuffer(array $output): void
    {
        if (empty($output)) {
            return;
        }

        echo $output[0];
        for ($i = 1; $i < count($output); $i++) {
            echo "\n\t" . $output[$i];
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
