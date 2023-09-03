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

namespace pinoox\component;

use pinoox\component\helpers\HelperString;
use pinoox\model\FileModel;

class Uploader extends Upload
{
    private $option = array();
    private $insert_ids = null;
    private $resultInsert = null;
    private $resultUpdate = null;
    private $resultEdit = null;
    private $tempFiles = array();
    private $isCommit = true;
    private $isInsertInAutoEdit = false;
    private $ids = null;

    public static function init($file = null, $folder = null)
    {
        self::$object = new Uploader($file, $folder);
        return self::$object;
    }


    public static function getInstance($file = null, $folder = null)
    {
        if (empty(self::$object)) {
            self::$object = new Uploader($file, $folder);
        } else {
            self::$object->file($file);
            self::$object->folder($folder);
        }
        return self::$object;
    }

    public function isCommit()
    {
        return $this->isCommit;
    }

    public function isInsert()
    {
        return $this->isInsertInAutoEdit;
    }

    public function getInsertId($isMulti = false)
    {
        if (isset($this->insert_ids) && is_array($this->insert_ids)) {
            if (count($this->insert_ids) == 1 && !$isMulti) return $this->insert_ids[0];
            else return $this->insert_ids;
        }
    }

    public function getId($isMulti = false)
    {
        if (isset($this->ids) && is_array($this->ids)) {
            if (count($this->ids) == 1 && !$isMulti) return $this->ids[0];
            else return $this->ids;
        }
    }

    public function deleteFolder()
    {
        $this->isFolderDelete = true;
        return self::$object;
    }

    public function popId()
    {
        $return = null;
        if (!empty($this->ids)) {
            $return = end($this->ids);
            array_pop($this->ids);
        }
        return $return;
    }

    public function shiftId()
    {
        $return = null;
        if (!empty($this->ids)) {
            $return = $this->ids[0];
            array_shift($this->ids);
        }
        return $return;
    }

    public function popInsertId()
    {
        $return = null;
        if (!empty($this->insert_ids)) {
            $return = end($this->insert_ids);
            array_pop($this->insert_ids);
        }
        return $return;
    }

    public function shiftInsertId()
    {
        $return = null;
        if (!empty($this->insert_ids)) {
            $return = $this->insert_ids[0];
            array_shift($this->insert_ids);
        }
        return $return;
    }

    public function getResultInsert($isMulti = false)
    {
        if (isset($this->resultInsert) && is_array($this->resultInsert)) {
            if (count($this->resultInsert) == 1 && !$isMulti) return current($this->resultInsert);
            else return $this->resultInsert;
        }
    }

    public function getResultUpdate()
    {
        return $this->resultUpdate;
    }

    public function getResultEdit($isMulti = false)
    {
        if (isset($this->resultEdit) && is_array($this->resultEdit)) {
            if (count($this->resultEdit) == 1 && !$isMulti) return current($this->resultEdit);
            else return $this->resultEdit;
        }
    }

    public function insert($access = null, $group = null, $user_id = null)
    {
        $this->isSave = true;
        $this->option['insert']['group'] = $group;
        $access = !is_null($access) ? $access : 'none';
        $this->option['insert']['access'] = $access;
        if (empty($user_id))
            $user_id = User::get('user_id');
        $this->option['insert']['user_id'] = $user_id;

        return self::$object;
    }

    public function update($file_id, $access = null)
    {
        $this->isSave = true;

        $this->option['update']['file_id'] = $file_id;
        if (!is_null($access))
            $this->option['update']['access'] = $access;

        return self::$object;
    }

    public function edit($file_id, $access = null, $group = null, $user_id = null)
    {
        $this->isSave = true;

        $this->option['edit']['file_id'] = $file_id;
        $this->option['edit']['group'] = $group;
        $access = !is_null($access) ? $access : 'none';
        $this->option['edit']['access'] = $access;
        if (empty($user_id)) {
            $user_id = User::get('user_id');
        }
        $this->option['edit']['user_id'] = $user_id;

        return self::$object;
    }

    public function removeDir($dir)
    {
        $this->isSave = true;
        $this->option['removeDir'] = $dir;

        return self::$object;
    }

    public function removeRow($file_id)
    {
        if (!empty($file_id)) {
            $this->isSave = true;
            $this->option['removeRow'] = $file_id;
        }

        return self::$object;
    }

    public function commit()
    {
        parent::commit();
        File::remove_file($this->tempFiles);
        $this->tempFiles = array();
    }

    protected function save($result)
    {
        $result['dir_file'] = HelperString::firstDelete($result['dir_file'], PINOOX_PATH);
        $result['dir_file'] = str_replace('\\', '/', $result['dir_file']);

        if (isset($this->option['insert'])) {
            $option = array_merge($this->option['insert'], $result);
            $this->actInsert($option);
        }

        if (isset($this->option['update'])) {
            $option = array_merge($this->option['update'], $result);
            $this->actUpdate($option);
            unset($this->option['update']);
        }

        if (isset($this->option['edit'])) {
            $option = array_merge($this->option['edit'], $result);
            $this->actEdit($option);
            unset($this->option['edit']);
        }

        if (isset($this->option['removeDir'])) {
            $option = $this->option['removeDir'];
            $this->actRemoveFile($option);
            unset($this->option['removeDir']);
        }

        if (isset($this->option['removeRow'])) {
            $option = $this->option['removeRow'];
            $this->actRemoveRow($option);
            unset($this->option['removeRow']);
        }

    }

    private function actInsert($option)
    {
        if ($id = FileModel::insert($option)) {

            $this->insert_ids[] = $id;
            $this->ids[] = $id;

            $this->resultInsert[$id] = array(
                'link' => Url::upload($id, false),
                "uploadname" => $option['uploadname'],
                "realname" => $option['realname'],
                "size" => $option['size'],
                "formattedSize" => $option['formattedSize'],
                "ext" => $option['ext'],
                'mimeType' => $option['mimeType'],
                'type' => $option['type'],
                'dir_file' => $option['dir_file'],
                'path_file' => $option['path_file']
            );
            if (isset($this->option['edit']))
                $this->resultEdit[$id] = $this->resultInsert[$id];

        } else {
            $this->isCommit = false;
        }
    }

    private function actUpdate($option)
    {
        $file = FileModel::fetch_by_id($option['file_id']);
        $path = Dir::path('~' . $file['file_path'] . $file['file_name']);
        if (FileModel::update($option)) {

            $this->ids[] = $option['file_id'];

            $this->actRemoveThumb($path);

            if (!$this->isTransaction) {
                File::remove_file($path);
            } else {
                $this->tempFiles[] = $path;
            }

            $this->resultUpdate[$option['file_id']] = array(
                'link' => Url::upload($option['file_id'], null,false),
                "uploadname" => $option['uploadname'],
                "realname" => $option['realname'],
                "size" => $option['size'],
                "formattedSize" => $option['formattedSize'],
                "ext" => $option['ext'],
                'mimeType' => $option['mimeType'],
                'type' => $option['type'],
                'dir_file' => $option['dir_file'],
                'path_file' => $option['path_file']
            );
            if (isset($this->option['edit']))
                $this->resultEdit[$option['file_id']] = $this->resultUpdate[$option['file_id']];
        } else {
            $this->isCommit = false;
        }
    }

    private function actRemoveThumb($file_path)
    {
        if (!is_file($file_path)) return;
        if (!isset($this->thumbImg["enable"])) return;
        $file_info = [];
        $file_info['ext'] = File::extension($file_path);
        if (!$this->isImg($file_info['ext'])) return;
        $file_info['dir'] = File::dir($file_path);
        $file_info['filename'] = File::fullname($file_path);
        $file_info['name'] = File::name($file_path);
        $path = $this->thumbImg["path"];
        $path = Dir::ds($path);
        $path = $file_info['dir'] . $this->dirSeparator . $path;


        $sizes = $this->thumbImg["size"];
        if (!is_array($sizes))
            $sizes = [$sizes];

            foreach ($sizes as $size) {
                $size = HelperString::lastDelete($size,'f');
                $file = HelperString::replaceData($path, [
                    'name' => $file_info['name'],
                    'ext' => $file_info['ext'],
                    'filename' => $file_info['filename'],
                    'size' => $size,
                ]);

                if (!$this->isTransaction) {
                    File::remove_file($file);
                } else {
                    $this->tempFiles[] = $file;
                }
            }
    }

    private function actEdit($option)
    {
        $isUpdate = true;
        if (empty($option['file_id'])) {
            $isUpdate = false;
        }

        if ($isUpdate) {
            $file = FileModel::fetch_by_id($option['file_id']);
            if (!$file) $isUpdate = false;
        }

        if ($isUpdate) {
            $this->actUpdate($option);
        } else {
            $this->isInsertInAutoEdit = true;
            $this->actRemoveFile($option['file_id']);
            $this->actInsert($option);
        }
    }

    private function actRemoveFile($file)
    {
        $this->actRemoveThumb($file);

        if (!$this->isTransaction) {
            File::remove_file($file);
        } else {
            $this->tempFiles[] = $file;
        }

    }

    public function actRemoveRow($row)
    {
        if (is_array($row)) {
            foreach ($row as $id) {
                $this->actRemoveRow($id);
            }
        } else {
            $file = FileModel::fetch_by_id($row);
            if (!$file) return self::$object;
            if (FileModel::delete($row)) {
                $path = Dir::path('~' . $file['file_path'] . $file['file_name']);
                $this->actRemoveThumb($path);
                if (!$this->isTransaction) {
                    File::remove_file($path);
                } else {
                    $this->tempFiles[] = $path;
                }
            } else {
                $this->isCommit = false;
            }
        }

        return self::$object;
    }
}