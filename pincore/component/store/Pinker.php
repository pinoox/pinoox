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


namespace pinoox\component\store;

use pinoox\component\Helpers\HelperAnnotations;
use pinoox\component\File;
use pinoox\component\Helpers\HelperObject;
use pinoox\component\Helpers\Str;

/**
 * Pinoox Baker
 * @package pinoox\component\store
 */
class Pinker
{
    /**
     * Data for pinoox baker
     * @var mixed
     */
    private $data = null;

    /**
     * @var bool
     */
    private bool $isOutputData = false;

    /**
     * Data dump status
     * @var mixed
     */
    private $dumping = false;

    /**
     * @var bool
     */
    private bool $isCamelToUnderscore = false;

    /**
     * Info for pinoox baker
     * @var ?array
     */
    private ?array $info = null;

    /**
     * File baked storage location
     * @var string
     */
    private string $bakedFile = '';

    /**
     * File baked storage location
     * @var string
     */
    private string $mainFile = '';


    /**
     * Set data for pinoox baker
     *
     * @param mixed $data
     * @return Pinker
     */
    public function data(mixed $data): Pinker
    {
        $this->data = $data;
        $this->isOutputData = true;
        return $this;
    }

    /**
     * Set info for pinoox baker
     *
     * @param array $info
     * @return Pinker
     */
    public function info(array $info): Pinker
    {
        $this->info = $info;

        return $this;
    }

    /**
     * get info for pinoox baker
     *
     * @param string|null $key
     * @return mixed|null
     */
    public function getInfo(?string $key = null): ?array
    {
        $info = HelperAnnotations::getTagsCurrentBlockInFile($this->bakedFile);
        return !is_null($key) ? @$info[$key] : $info;
    }

    /**
     * Change data dump status
     *
     * @param bool $status
     * @return Pinker
     */
    public function dumping(bool $status = true): Pinker
    {
        $this->dumping = $status;

        return $this;
    }

    public function __construct(string $mainFile = '', string $bakedFile = '')
    {
        $this->mainFile = $mainFile;
        $this->bakedFile = $bakedFile;
    }

    /**
     * create pinker object
     * @param string|null $mainFile
     * @param string|null $bakedFile
     * @return static
     */
    public static function create(?string $mainFile = null, ?string $bakedFile = null): static
    {
        return new static($mainFile, $bakedFile);
    }

    /**
     * Bake file
     */
    public function bake(): Pinker
    {
        if (!empty($this->bakedFile)) {
            if (!$this->dumping) {
                $config = $this->format($this->generateData());
            } else {
                $config = $this->transmutation();
            }

            File::generate($this->bakedFile, $config);
        }

        return $this;
    }

    /**
     * Data storage format
     *
     * @param mixed $data
     * @return string
     */
    private function format(mixed $data): string
    {
        $tags = $this->generateInformation();
        $docBlock = HelperAnnotations::generateDocBlock('Pinoox Baker', $tags);

        return '<?' . 'php' . "\n" .
            $docBlock . "\n\n" .
            'return ' . var_export($data, true) . ';';
    }

    private function transmutation(): mixed
    {
        $data = $this->generateData();
        $replaces = [];
        $printData = [];

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $k = $this->isCamelToUnderscore ? Str::camelToUnderscore($k) : $k;
                if (is_callable($v)) {
                    $replaces['{_{' . $k . '}_}'] = HelperObject::closure_dump($v);
                    $printData[$k] = '{_{' . $k . '}_}';
                } else {
                    $printData[$k] = $v;
                }
            }
        } else if (is_callable($data)) {
            $replaces['{_{dump}_}'] = HelperObject::closure_dump($data);
            $printData = '{_{dump}_}';
        } else {
            $printData = $data;
        }

        $printData = $this->format($printData);

        foreach ($replaces as $k => $v) {
            $printData = str_replace("'$k'", $v, $printData);
        }

        return $printData;
    }

    public function camelToUnderscore(bool $status = true)
    {
        $this->isCamelToUnderscore = $status;
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

        if (is_array($data) && isset($data['__pinker__']) && $data['__pinker__'] == true) {
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

    /**
     * Generate data for bake
     *
     * @return mixed
     */
    private function generateData(): mixed
    {
        $data = $this->data;
        if (is_array($data) && isset($data['__pinker__']) && $data['__pinker__'] == true) {
            return @$data['data'];
        }

        return $data;
    }

    /**
     * Get the baked file information
     */
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

    /**
     * Remove the baked file
     */
    public function remove()
    {
        File::remove_file($this->bakedFile);
    }

    /**
     * Refresh the baked file
     */
    public function restore()
    {
        File::remove_file($this->bakedFile);
        $this->data = is_file($this->mainFile) ? (include $this->mainFile) : null;
        $this->bake();
    }

    /**
     * get config data file
     *
     * @return mixed
     */
    private function getData(): mixed
    {
        if (!is_file($this->bakedFile)) {
            $this->data = is_file($this->mainFile) ? (include $this->mainFile) : null;
            $this->bake();
        }

        if ($this->isOutputData)
            return $this->data;

        return is_file($this->bakedFile) ? (include $this->bakedFile) : null;
    }

    /**
     * Building pinker data
     *
     * @param mixed $data
     * @param array $info
     * @return array
     */
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
}