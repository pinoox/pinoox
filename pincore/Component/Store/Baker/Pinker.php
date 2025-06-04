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


namespace Pinoox\Component\Store\Baker;

use Closure;
use Pinoox\Component\Helpers\HelperAnnotations;
use Pinoox\Component\Helpers\HelperObject;
use Pinoox\Component\Helpers\Str;

/**
 * Class to manage Pinker data
 */
class Pinker
{
    private $data = null;

    private ?array $info = null;
    private bool $isOutputData = false;
    private bool $dumping = false;
    private bool $isCamelToUnderscore = false;
    private string $bakedFile = '';
    private string $mainFile = '';
    private FileHandlerInterface $fileHandler;
    /**
     * @var Pinker[]
     */
    private array $objs = [];

    public function __construct(?string $mainFile = '', ?string $bakedFile = '', ?FileHandlerInterface $fileHandler = null)
    {
        $this->mainFile = $mainFile;
        $this->bakedFile = $bakedFile;
        $this->fileHandler = $fileHandler ?? new FileHandler();
    }

    public static function create(?string $mainFile = '', ?string $bakedFile = ''): static
    {
        return new static($mainFile, $bakedFile);
    }

    public function data($data): self
    {
        $this->data = $data;
        $this->isOutputData = true;
        return $this;
    }

    public function dumping(bool $status = true): self
    {
        $this->dumping = $status;
        return $this;
    }

    public function camelToUnderscore(bool $status = true): self
    {
        $this->isCamelToUnderscore = $status;
        return $this;
    }

    public function bake(): self
    {
        if (!empty($this->bakedFile)) {
            if (!$this->dumping) {
                $config = $this->format($this->generateData());
            } else {
                $config = $this->transmutation();
            }
            $this->fileHandler->store($this->bakedFile, $config);
        }

        return $this;
    }

    private function format($data): string
    {
        $tags = $this->generateInformation();
        $docBlock = HelperAnnotations::generateDocBlock('Pinoox Baker', $tags);

        return '<?' . 'php' . "\n" .
            $docBlock . "\n\n" .
            'return ' . var_export($data, true) . ';';
    }

    /**
     * Generate info for bake
     *
     * @return array
     */
    private function generateInformation(): array
    {
        $data = $this->data;
        $mainInfo = $this->info;
        $mainInfo = !is_array($mainInfo) ? [] : $mainInfo;
        $info = [];

        if (is_array($data) && isset($data['__pinker__']) && $data['__pinker__']) {
            $info = !is_array($data['info']) ? [] : $data['info'];;
        }

        return array_merge(
            [
                'time' => time(),
            ],
            $info,
            $mainInfo,
        );
    }

    private function transmutation(): array|string
    {
        $data = $this->generateData();
        $replaces = [];
        $printData = [];

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $key = $this->isCamelToUnderscore ? Str::camelToUnderscore($key) : $key;
                if (is_callable($value) && $value instanceof Closure) {
                    $replaces['{_{' . $key . '}_}'] = HelperObject::closure_dump($value);
                    $printData[$key] = '{_{' . $key . '}_}';
                } else {
                    $printData[$key] = $value;
                }
            }
        } elseif (is_callable($data) && $data instanceof Closure) {
            $replaces['{_}'] = HelperObject::closure_dump($data);
            $printData = '{_}';
        } else {
            $printData = $data;
        }

        $printData = $this->format($printData);

        foreach ($replaces as $key => $value) {
            $printData = str_replace("'$key'", $value, $printData);
        }

        return $printData;
    }

    public function pickup()
    {
        $data = $this->getData();
        $info = $this->getInfo();
        $lifetime = @$info['lifetime'];

        if (!empty($lifetime) && is_numeric($lifetime)) {
            $lifetime = @$info['time'] + $lifetime;

            if ($lifetime < time()) {
                $this->remove();
                $data = $this->getData();
            }
        }

        return @$data;
    }

    public function remove(): void
    {
        if(is_file($this->bakedFile))
            $this->fileHandler->remove($this->bakedFile);
    }

    public function getInfo(?string $key = null): array|string|null
    {
        $info = HelperAnnotations::getTagsCurrentBlockInFile($this->bakedFile);
        return !is_null($key) ? @$info[$key] : $info;
    }

    public function restore(): void
    {
        $this->fileHandler->remove($this->bakedFile);
        $this->data = is_file($this->mainFile) ? $this->fileHandler->retrieve($this->mainFile) : null;
        $this->bake();
    }

    private function getData()
    {
        if (!is_file($this->bakedFile) && is_file($this->mainFile)) {
            $this->data = is_file($this->mainFile) ? $this->fileHandler->retrieve($this->mainFile) : null;
            $this->bake();
        }

        return $this->isOutputData ? $this->data : (is_file($this->bakedFile) ? $this->fileHandler->retrieve($this->bakedFile) : null);
    }

    private function generateData()
    {
        $data = $this->data;
        if (is_array($data) && isset($data['__pinker__']) && $data['__pinker__'] === true) {
            return @$data['data'];
        }

        return $data;
    }

    public function build($data, array $info = []): array
    {
        if (is_callable($data)) {
            $data = $data();
        }

        return [
            'data' => $data,
            'info' => $info,
            '__pinker__' => true,
        ];
    }

    /**
     * @return string
     */
    public function getBakedFile(): string
    {
        return $this->bakedFile;
    }

    /**
     * @return string
     */
    public function getMainFile(): string
    {
        return $this->mainFile;
    }
}