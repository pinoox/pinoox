<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */


namespace Pinoox\Component;

use InvalidArgumentException;

/**
 * Class StubGenerator
 *
 * A utility class to generate files from stub templates.
 */
class StubGenerator
{
    /**
     * The path to the directory containing stub templates.
     *
     * @var string
     */
    protected string $stubsPath;

    /**
     * StubGenerator constructor.
     *
     * @param string $stubsPath The path to the directory containing stub templates.
     */
    public function __construct(string $stubsPath)
    {
        $this->stubsPath = rtrim($stubsPath, '/') . '/';
    }

    /**
     * Generate a file from the given stub template and data.
     *
     * @param string $stubFileName The name of the stub template file.
     * @param string $outputPath The path of the generated output file.
     * @param array $data An associative array containing placeholders and their replacements.
     *
     * @throws InvalidArgumentException If the stub file does not exist.
     */
    public function generate(string $stubFileName, string $outputPath, array $data = []): void
    {
        $stubContents = $this->loadStub($stubFileName);
        $generatedCode = $this->replacePlaceholders($stubContents, $data);

        $this->saveGeneratedFile($outputPath, $generatedCode);
    }

    /**
     * Get the content of a stub template with placeholders replaced by actual data.
     *
     * @param string $stubFileName The name of the stub template file.
     * @param array $data An associative array containing placeholders and their replacements.
     *
     * @return string The content of the stub template with placeholders replaced by their respective values.
     *
     * @throws InvalidArgumentException If the stub file does not exist.
     */
    public function get(string $stubFileName, array $data = []): string
    {
        $stubContents = $this->loadStub($stubFileName);
        return $this->replacePlaceholders($stubContents, $data);
    }

    /**
     * Load the contents of a stub template.
     *
     * @param string $stubFileName The name of the stub template file.
     *
     * @return string The contents of the stub template.
     *
     * @throws InvalidArgumentException If the stub file does not exist.
     */
    protected function loadStub(string $stubFileName): string
    {
        $stubFilePath = $this->stubsPath . $stubFileName;
        if (!file_exists($stubFilePath)) {
            throw new InvalidArgumentException("Stub file not found: {$stubFileName}");
        }

        return file_get_contents($stubFilePath);
    }

    /**
     * Replace placeholders in the content with actual data.
     *
     * @param string $content The content of the stub template.
     * @param array $data An associative array containing placeholders and their replacements.
     *
     * @return string The content with placeholders replaced by their respective values.
     */
    protected function replacePlaceholders(string $content, array $data): string
    {
        // Array to store found placeholders
        $foundPlaceholders = [];

        foreach ($data as $placeholder => $value) {
            // Check if the placeholder exists in the content
            if (preg_match("/\{\{\s*{$placeholder}\s*\}\}/", $content)) {
                // Replace the placeholder in the content
                $content = preg_replace("/\{\{\s*{$placeholder}\s*\}\}/", $value, $content);
                // Add the placeholder to the list of found placeholders
                $foundPlaceholders[] = $placeholder;
            }
        }

        // Remove any unused placeholders from the content
        return preg_replace_callback(
            '/\{\{\s*(.*?)\s*\}\}.+?\{\{\s*\/\1\s*\}\}\n?/s',
            function ($match) use ($foundPlaceholders) {
                return in_array($match[1], $foundPlaceholders) ? $match[0] : '';
            },
            $content
        );
    }

    /**
     * Save the generated content to a file.
     *
     * @param string $outputFile The generated output file.
     * @param string $generatedCode The content to be saved in the file.
     */
    protected function saveGeneratedFile(string $outputFile, string $generatedCode): bool
    {
        $result = file_put_contents($outputFile, $generatedCode);
        return $result !== false;
    }
}
