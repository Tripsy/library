<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Tripsy\Data\DataFileHandleUpload;
use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Exceptions\SystemException;
use Tripsy\Library\Exceptions\TemplateException;
use Tripsy\Library\Standard\ArrayDot;
use Tripsy\Library\Standard\FileTools;
use Tripsy\Library\Standard\Output;
use Tripsy\Library\Standard\StringTools;

class FileHandle
{
    protected Config $cfg;
    protected Language $language;

    private array $file_config = [
        'overwrite' => true, //overwrite file if already exist
        'extension' => true, //enable / disable use of file extension in file name
        'check' => array(
            'type' => [],
            'size' => null,
            'res_min' => null,
            'res_max' => null
        )
    ];

    private string $file_name = ''; //set if specific file name is needed
    private int $file_id;
    private string $file_label; //file label, usually file folder
    private string $file_case = 'image'; //image OR file
    private string $file_input = 'image'; //form input name
    private int $file_number; //form number of files which can be uploaded

    /**
     * @param Config $cfg
     * @param Language $language
     */
    public function __construct(Config $cfg, Language $language)
    {
        $this->cfg = $cfg;
        $this->language = $language;
    }

    /**
     * Setter for `file_label`
     * Notes: This method has to used everytime after class is initialized
     *
     * @param string $string
     * @return $this
     * @throws ConfigException
     */
    public function setFileLabel(string $string): self
    {
        $this->file_label = $string;

        if ($this->cfg->has($this->file_label . '.upload') === false) {
            throw new ConfigException('Settings not available for this file label (eg: ' . $this->file_label . ')');
        }

        $config = $this->cfg->get($this->file_label . '.upload');

        $this->setFileNumber($config['number']);

        $this->setFileConfig('check.type', $config['type']);
        $this->setFileConfig('check.size', $config['size']);
        $this->setFileConfig('check.res_min', $config['res_min'] ?? null);
        $this->setFileConfig('check.res_max', $config['res_max'] ?? null);

        return $this;
    }

    /**
     * Getter for `file_label`
     *
     * @return string
     * @throws ConfigException
     */
    private function getFileLabel(): string
    {
        if (empty($this->file_label) === true) {
            throw new ConfigException('File label is not defined');
        }

        return $this->file_label;
    }

    /**
     * Setter for `file_case`
     *
     * @param string $string
     * @return $this
     */
    public function setFileCase(string $string): self
    {
        if ($string) {
            $this->file_case = $string;
        }

        return $this;
    }

    /**
     * Getter for `file_case`
     *
     * @return string
     * @throws ConfigException
     */
    private function getFileCase(): string
    {
        if (empty($this->file_case) === true) {
            throw new ConfigException('File case is not defined');
        }

        return $this->file_case;
    }

    /**
     * Setter for `file_input`
     *
     * @param string $string
     * @return $this
     */
    public function setFileInput(string $string): self
    {
        if ($string) {
            $this->file_input = $string;
        }

        return $this;
    }

    /**
     * Getter for `file_input`
     *
     * @return string
     * @throws ConfigException
     */
    private function getFileInput(): string
    {
        if (empty($this->file_input) === true) {
            throw new ConfigException('File input is not defined');
        }

        return $this->file_input;
    }

    /**
     * Setter for `file_id`
     *
     * @param int $int
     * @return $this
     */
    public function setFileId(int $int): self
    {
        if ($int) {
            $this->file_id = $int;
        }

        return $this;
    }

    /**
     * Getter for `file_id`
     *
     * @return int
     * @throws ConfigException
     */
    private function getFileId(): int
    {
        if (empty($this->file_id) === true) {
            throw new ConfigException('File id is not defined');
        }

        return $this->file_id;
    }

    /**
     * Setter for `file_name`
     *
     * @param int $string
     * @return $this
     */
    public function setFileName(int $string): self
    {
        if ($string) {
            $this->file_name = $string;
        }

        return $this;
    }

    /**
     * Getter for `file_name`
     *
     * @return string
     */
    private function getFileMame(): string
    {
        return $this->file_name;
    }

    /**
     * Setter for `file_number`
     * Notes: Sets the number of files available for upload
     *
     * @param string $int
     * @return void
     */
    private function setFileNumber(string $int)
    {
        if ($int) {
            $this->file_number = $int;
        }
    }

    /**
     * Getter for `file_number`
     *
     * @return int
     * @throws ConfigException
     */
    private function getFileNumber(): int
    {
        if (empty($this->file_number) === true) {
            throw new ConfigException('File number is not defined');
        }

        return $this->file_number;
    }

    /**
     * Set `file_config`
     *
     * @param string $key
     * @param $value
     * @return void
     */
    private function setFileConfig(string $key, $value): void
    {
        ArrayDot::set($this->file_config, $key, $value);
    }

    /**
     * Getter for `file_config`
     *
     * @param string $key
     * @return mixed
     */
    private function getFileConfig(string $key): mixed
    {
        return ArrayDot::get($this->file_config, $key);
    }

    /**
     * Generate safe file name based on in (eg: upload name) or out if set (eg: specified name)
     *
     * @param string $in
     * @param string $out
     * @param string $mode
     * @return string
     */
    private function safeFileName(string $in, string $out = '', string $mode = 'raw'): string
    {
        $file_info = pathinfo($in);
        $file_name = $out ?: $file_info['filename'];

        $file_name = StringTools::safe_chars($file_name);
        $file_name = preg_replace('/[^a-z0-9-_]/', '', strtolower($file_name));

        if ($mode == 'db') {
            $file_name = substr($file_name, 0, 40);
            $file_name .= '_' . StringTools::random_code(5);
        }

        $file_name .= $this->getFileConfig('extension') ? '.' . strtolower($file_info['extension']) : '';

        return $file_name;
    }

    /**
     * Generate file name based on format (eg: default, thumb, small, etc.)
     *
     * @param string $file_name
     * @param string $format
     * @return string
     */
    private function fileFormatName(string $file_name, string $format = 'default'): string
    {
        $file_info = pathinfo($file_name);

        $file_name = $file_info['filename'];
        $file_name .= $format != 'default' ? '_' . $format : null;
        $file_name .= $this->getFileConfig('extension') ? '.' . $file_info['extension'] : null;

        return $file_name;
    }

    /**
     * Determine folder path based on `file_label` and `file_id`
     *
     * @param int $file_id
     * @return string
     * @throws ConfigException
     */
    private function getFolder(int $file_id = 0): string
    {
        $file_label = $this->getFileLabel();
        $file_id = $file_id ?: $this->getFileId();

        return $this->cfg->get($file_label . '.folder') . '/' . (floor($file_id / 500) * 500) . '/' . $file_id;
    }

    /**
     * Determine absolute folder path
     *
     * @param int $file_id
     * @return string
     * @throws ConfigException
     */
    private function getFolderPath(int $file_id = 0): string
    {
        return $this->cfg->get('path.files') . $this->getFolder($file_id);
    }

    /**
     * Determine absolute file path
     *
     * @param string $file_name
     * @param int $file_id
     * @return string
     * @throws ConfigException
     */
    private function getFilePath(string $file_name, int $file_id = 0): string
    {
        if ($file_name) {
            return $this->getFolderPath($file_id) . '/' . $file_name;
        }

        return '';
    }

    /**
     * Get file name from form files
     *
     * @param array $form_files
     * @return string
     * @throws ConfigException
     */
    public function getFile(array $form_files): string
    {
        $result = $this->process($form_files);

        return $result[0]['file_name'] ?? '';
    }

    /**
     * Get files array
     *
     * @param array $form_files
     * @return array
     * @throws ConfigException
     */
    public function getFiles(array $form_files): array
    {
        return $this->process($form_files);
    }

    /**
     * Prepare files array
     *
     * @param array $form_files
     * @return array
     * @throws ConfigException
     * @throws SystemException
     */
    private function process(array $form_files): array
    {
        $input_files = $form_files[$this->getFileInput()] ?? [];

        $result = [];

        foreach ($input_files as $f) {
            $result[] = array(
                'file_name_temp' => $f['file_name'],
                'file_name' => $f['file_source'] == 'db' ? $f['file_name'] : $this->process_move($f['file_name']),
                'file_source' => $f['file_source'],
            );
        }

        return $result;
    }

    /**
     * Move file from source to proper destination
     * Notes: If is an image also generate thumb
     *
     * @param string $upload_name
     * @return string
     * @throws ConfigException
     * @throws SystemException
     */
    private function process_move(string $upload_name): string
    {
        $path_file_source = $this->cfg->get('path.temp') . '/' . $upload_name;

        if (file_exists($path_file_source) === false) {
            throw new SystemException('File not found (eg: ' . $path_file_source . ')');
        }

        $save_name = $this->getFileMame();
        $save_name = $this->safeFileName($upload_name, $save_name, 'db');
        $path_dest = $this->getFolderPath();

        FileTools::createFolder($path_dest);

        $path_file_dest = $path_dest . '/' . $save_name;

        if (@rename($path_file_source, $path_file_dest)) {
            //procedure -> generate thumb
            if ($this->getFileCase() == 'image') {

                $file_label = $this->getFileLabel();

                $arr_format = $this->cfg->has($file_label . '.upload.format') ? $this->cfg->get($file_label . '.upload.format') : [];

                foreach ($arr_format as $format => $info) {
                    if (empty($info['dimension'])) {
                        continue;
                    }

                    $format_name = $this->fileFormatName($save_name, $format);
                    $path_format_dest = $this->getFilePath($format_name);

                    $this->thumb($path_file_dest, $path_format_dest, $info['dimension']);
                }
            }

            return $save_name;
        } else {
            throw new SystemException('Error while moving file ' . $path_file_source . ' to ' . $path_file_dest);
        }
    }

    /**
     * Generate thumb for image
     *
     * @param string $path_file_source
     * @param string $path_file_dest
     * @param string $dimension
     * @return void
     * @throws ConfigException
     */
    public function thumb(string $path_file_source, string $path_file_dest, string $dimension): void
    {
        if (file_exists($path_file_source) === false) {
            throw new SystemException('File not found (eg: ' . $path_file_source . ')');
        }

        $dimension = explode('x', $dimension);
        $new_w = isset($dimension[0]) ? (int)$dimension[0] : 0;
        $new_h = isset($dimension[1]) ? (int)$dimension[1] : 0;

        try {
            if (class_exists('Imagick', false)) {
                $imagick = new \Imagick($path_file_source);
            } else {
                $imagick = new ImagickCustom($path_file_source);
            }

            $imagick->scaleImage($new_w, $new_h);
            $imagick->writeImage($path_file_dest);
        } catch (\ImagickException $e) {
            throw new SystemException($e->getMessage());
        }
    }

    /**
     * Get file link
     *
     * @param string $file_name
     * @param int $file_id
     * @return string
     * @throws ConfigException
     */
    public function getFileLink(string $file_name, int $file_id): string
    {
        if ($file_name) {
            return $this->cfg->get('link.files') . $this->getFolder($file_id) . '/' . $file_name;
        }

        return '';
    }

    /**
     * Generate image output
     *
     * @param string $file_name
     * @param int $file_id
     * @param string $format
     * @param string $output
     * @param array $attr
     * @return string
     * @throws ConfigException
     */
    public function image(string $file_name, int $file_id, string $format, string $output = 'src', array $attr = []): string
    {
        if (empty($file_name) === true) {
            return '';
        }

        $return = $this->getFileLink($this->fileFormatName($file_name, $format), $file_id);

        switch ($output) {
            case 'src':
                return $return;
            default:
                return '<img src="' . $return . '" ' . implode(' ', $attr) . ' />';
        }
    }

    /**
     * Generate form element for upload
     *
     * @param array $files
     * @param int $file_id
     * @param string $file_input
     * @return string
     * @throws ConfigException
     * @throws TemplateException     *
     */
    public function uploadBox(array $files, int $file_id, string $file_input = ''): string
    {
        if ($file_input) {
            $this->setFileInput($file_input);
        }

        $file_input = $this->getFileInput();

        $files_html = '';

        if (empty($files[$file_input]) === false) {
            foreach ($files[$file_input] as $v) {
                $files_html .= $this->uploadBoxFile($v['file_name'], $file_id, $v['file_source']);
            }
        }

        $file_allowed = str_replace('image/', '*.', implode(', ', $this->getFileConfig('check.type')));
        $file_max_size = FileTools::displayFileSize($this->getFileConfig('check.size'));
        $file_number = $this->getFileNumber();

        return template('upload_box')
            ->assign('multiple', $file_number > 1)
            ->assign('file_label', $this->getFileLabel())
            ->assign('file_input', $this->getFileInput())
            ->assign('file_case', $this->getFileCase())
            ->assign('file_id', $file_id)
            ->assign('file_number', $file_number)
            ->assign('file_allowed', $file_allowed)
            ->assign('file_max_size', $file_max_size)
            ->assign('files', $files_html)
            ->parse();
    }

    /**
     * File entry from upload box
     *
     * @param string $file_name
     * @param int $file_id
     * @param string $file_source
     * @return string
     * @throws ConfigException
     * @throws TemplateException
     */
    public function uploadBoxFile(string $file_name, int $file_id, string $file_source = 'temp'): string
    {
        switch ($file_source) {
            case 'temp':
                $file_link = $this->cfg->get('link.site') . $this->cfg->get('folder.temp') . '/' . $file_name;
                break;
            case 'db':
                $file_link = $this->getFileLink($file_name, $file_id);
                break;
            default:
                throw new ConfigException('File source (eg: ' . $file_source . ' case not defined');
        }

        return template('upload_box_file')
            ->assign('file_source', $file_source)
            ->assign('file_name', $file_name)
            ->assign('file_link', $file_link)
            ->assign('file_key', StringTools::random_code(8))
            ->assign('file_input', $this->getFileInput())
            ->assign('file_case', $this->getFileCase())
            ->parse();
    }

    /**
     * Upload file to temp folder
     *
     * @param FileUploaded $file
     * @param string $save_name
     * @param string $upload_path
     * @return Output
     * @throws ConfigException
     * @throws ConfigException
     * @throws SystemException
     */
    public function upload(FileUploaded $file, string $save_name = '', string $upload_path = ''): Output
    {
        $outputData = new DataFileHandleUpload();
        $output = new Output($outputData);

        if ($file->getError()) {
            $output->fail($this->language->get('file.error.upload_action'), [
                'code' => $file->getError(),
                'file_name' => $file->getName()
            ]);

            return $output;
        }

        if ($upload_path) {
            //check if directory exist
            if (file_exists($upload_path) === true) {
                throw new ConfigException('Upload path not found (eg: ' . $upload_path . ')');
            }
        } else {
            $upload_path = $this->cfg->get('path.temp');
        }

        $output->data->set('file_name', $this->safeFileName($file->getName(), $save_name));
        $output->data->set('file_path', $upload_path . '/' . $output->data->get('file_name'));

        //check if file already exist
        if ($this->getFileConfig('overwrite') === false && file_exists($output->data->get('file_path'))) {
            $output->fail($this->language->get('file.error.file_exist'), [
                'file_name' => $output->data->get('file_name'),
                'file_path' => $output->data->get('file_path')
            ]);

            return $output;
        }

        $output->data->set('file_mime_type', $file->getMimeContentType());

        //verification -> mime type
        if ($this->getFileConfig('check.type') && !in_array($output->data->get('file_mime_type'), $this->getFileConfig('check.type'))) {
            $output->fail($this->language->get('file.error.invalid_type'), [
                'file_name' => $output->data->get('file_name'),
                'file_type' => $output->data->get('file_mime_type')
            ]);

            return $output;
        }

        $output->data->set('file_size', $file->getSize());

        //check file size
        if ($this->getFileConfig('check.size') && $output->data->get('file_size') > $this->getFileConfig('check.size')) {
            $output->fail($this->language->get('file.error.check_size'), [
                'file_name' => $output->data->get('file_name'),
                'file_size' => $output->data->get('file_size'),
                'max_size' => $this->getFileConfig('check.size'),
            ]);

            return $output;
        }

        //check resolution
        if ($this->getFileCase() == 'image' && ($this->getFileConfig('check.res_min') || $this->getFileConfig('check.res_max'))) {
            $img_size = FileTools::getImageResolution($file->getTmpName());

            //check min resolution
            if ($this->getFileConfig('check.res_min')) {
                $img_size_min = explode('x', $this->getFileConfig('check.res_min'));

                //check width
                if ($img_size_min[0] && $img_size_min[0] > $img_size['width']) {
                    $output->fail($this->language->get('file.error.min_width'), [
                        'file_name' => $output->data->get('file_name'),
                        'min_width' => $img_size_min[0],
                        'img_width' => $img_size['width'],
                    ]);

                    return $output;
                }

                //check height
                if ($img_size_min[1] && $img_size_min[1] > $img_size['height']) {
                    $output->fail($this->language->get('file.error.min_height'), [
                        'file_name' => $output->data->get('file_name'),
                        'min_height' => $img_size_min[1],
                        'img_height' => $img_size['height'],
                    ]);

                    return $output;
                }
            }

            //check max resolution
            if ($this->getFileConfig('check.res_max')) {
                $img_size_max = explode('x', $this->getFileConfig('check.res_max'));

                //check width
                if ($img_size_max[0] && $img_size_max[0] < $img_size['width']) {
                    $output->fail($this->language->get('file.error.max_width'), [
                        'file_name' => $output->data->get('file_name'),
                        'max_width' => $img_size_max[0],
                        'img_width' => $img_size['width'],
                    ]);

                    return $output;
                }

                //check height
                if ($img_size_max[1] && $img_size_max[1] < $img_size['height']) {
                    $output->fail($this->language->get('file.error.max_height'), [
                        'file_name' => $output->data->get('file_name'),
                        'max_height' => $img_size_max[1],
                        'img_height' => $img_size['height'],
                    ]);

                    return $output;
                }
            }
        }

        if (!move_uploaded_file($file->getTmpName(), $output->data->get('file_path'))) {
            $output->fail($this->language->get('file.error.upload_move'), [
                'file_name' => $output->data->get('file_name'),
                'file_path' => $output->data->get('file_path'),
            ]);

            return $output;
        }

        return $output->success();
    }

    /**
     * Remove file
     *
     * @param string $file_source
     * @param string $file_name
     * @param int $file_id
     * @return void
     * @throws ConfigException
     */
    public function file_remove(string $file_source, string $file_name, int $file_id): void
    {
        if (empty($file_name) === true) {
            return;
        }

        if ($file_source == 'temp') {
            FileTools::removeFile($this->cfg->get('path.temp') . '/' . $file_name);

            return;
        }

        $this->setFileId($file_id);

        $file_format = $this->cfg->get($this->getFileLabel() . '.upload.format', []);
        $arr_format = array_unique(array_merge(
            array_keys($file_format),
            array('default')
        ));

        foreach ($arr_format as $format) {
            FileTools::removeFile($this->getFilePath($this->fileFormatName($file_name, $format), $file_id));
        }
    }

    /**
     * Rotate image
     *
     * @param string $file_path
     * @param int $rotate
     * @return void
     * @throws ConfigException
     */
    public function rotate(string $file_path, int $rotate)
    {
        try {
            if (class_exists('Imagick', false)) {
                $imagick = new \Imagick($file_path);
            } else {
                $imagick = new ImagickCustom($file_path);
            }

            $imagick->rotateImage('#ffffff', $rotate);
        } catch (\ImagickException $e) {
            throw new SystemException($e->getMessage());
        }
    }

    /**
     * Crop image
     *
     * @param string $file_path
     * @param int $width
     * @param int $height
     * @param int $x
     * @param int $y
     * @return void
     * @throws ConfigException
     */
    public function crop(string $file_path, int $width = 200, int $height = 200, int $x = 0, int $y = 0)
    {
        try {
            if (class_exists('Imagick', false)) {
                $imagick = new \Imagick($file_path);
            } else {
                $imagick = new ImagickCustom($file_path);
            }

            $imagick->cropImage($width, $height, $x, $y);
            $imagick->writeImage($file_path);
        } catch (\ImagickException $e) {
            throw new ConfigException($e->getMessage());
        }
    }

    /**
     * Edit image
     *
     * @param string $file_name
     * @return string
     * @throws TemplateException
     */
    public function uploadBoxImageEdit(string $file_name): string
    {
        $file_link = $this->cfg->get('link.site') . $this->cfg->get('folder.temp') . '/' . $file_name;
        $file_path = $this->cfg->get('path.temp') . '/' . $file_name;

        $image_res = FileTools::getImageResolution($file_path);

        function gcd($w, $h)
        {
            return ($h == 0) ? $w : gcd($h, $w % $h);
        }

        $r = gcd($image_res['width'], $image_res['height']);

        $jcrop = [
            'min_width' => 50,
            'min_height' => 50,
            'source_width' => $image_res['width'],
            'source_height' => $image_res['height'],
            'resize_width' => $image_res['width'],
            'resize_height' => $image_res['height'],
            'scale_factor' => 1,
            'aspect_ratio' => $image_res['width'] / $r . ' / ' . $image_res['height'] / $r,
        ];

        if ($jcrop['source_width'] > 400) {
            $jcrop['resize_width'] = 400;
            $jcrop['resize_height'] = round($jcrop['resize_width'] * $jcrop['source_height'] / $jcrop['source_width']);
            $jcrop['scale_factor'] = round($jcrop['source_width'] / $jcrop['resize_width'], 2);
        }

        return template('upload_box_image_edit')
            ->assign('file_link', $file_link)
            ->assign('time', time())
            ->assign('jcrop', $jcrop)
            ->parse();
    }
}

//
//    public function show_image_list($section, $id_entry, $format = 'small')
//    {
//        //vars
//        $arr_image = $this->load(array(
//            'section'  => $section,
//            'id_entry' => $id_entry,
//        ));
//
//        //loop
//        foreach ($arr_image as $k => $v)
//        {
//            //condition
//            if (is_array($format))
//            {
//                //loop
//                foreach ($format as $f)
//                {
//                    //vars
//                    $arr_image[$k][$f] = $this->imageSrc(array(
//                        'file_name'  => $v['name'],
//                        'file_label' => $section,
//                        'file_id'    => $id_entry,
//                        'format'     => $f,
//                    ));
//                }
//            }
//            else
//            {
//                //vars
//                $arr_image[$k]['src'] = $this->imageSrc(array(
//                    'file_name'  => $v['name'],
//                    'file_label' => $section,
//                    'file_id'    => $id_entry,
//                    'format'     => $format,
//                ));
//            }
//        }
//
//        //return
//        return $arr_image;
//    }

//    public function save($vars, $arr_input)
//    {
//          https://www.php.net/manual/en/ds-map.xor.php  implement this
//        //vars
//        $vars = (object) array_merge(array(
//            'action'   => null,
//            'section'  => null,
//            'is_image' => false,
//            'id_entry' => 0
//        ), $vars);
//
//        //vars
//        $sql_table = $vars->section.'_image';
//
//        //condition
//        if ($vars->action == 'update')
//        {
//            //init vars
//            $arr_source_db = [];
//
//            //condition
//            if ($arr_input)
//            {
//                //vars
//                $arr_source_db = array_filter(array_map(function($v) {
//                    return $v['file_source'] == 'db' ? $v['file_name'] : null;
//                }, $arr_input));
//            }
//
//            //condition
//            if ($arr_source_db)
//            {
//                //query -> param
//                $sql_data = $this->sql->bind($arr_source_db);
//
//                //query -> delete
//                $this->sql->delete(array(
//                    'table' => $sql_table,
//                    'where' => array(
//                        'id_entry = :id_entry',
//                        'name NOT IN ('.$sql_data['where'].')',
//                    ),
//                    'bind'  => array_merge($sql_data['bind'], array(
//                        'id_entry' => $vars->id_entry,
//                    ))
//                ));
//            }
//            else
//            {
//                //query -> delete
//                $this->sql->delete(array(
//                    'table' => $sql_table,
//                    'where' => array(
//                        'id_entry = :id_entry',
//                    ),
//                    'bind'  => array(
//                        'id_entry' => $vars->id_entry,
//                    )
//                ));
//            }
//        }
//
//        //condition
//        if (!$arr_input)
//            return;
//
//        //init vars
//        $binds_i = []; //binds - insert
//        $binds_s = []; //binds - update - sequence
//        $s       = 0;
//
//        //loop
//        foreach ($arr_input as $v)
//        {
//            //increment
//            $s++;
//
//            //vars
//            $binds_s[] = array('id_entry' => $vars->id_entry, 'name' => $v['file_name'], 'sequence' => $s);
//
//            //condition
//            if ($v['file_source'] == 'db')
//                continue; //skip
//
//            //vars
//            $file_path = $this->getFilePath($v['file_name'], null, $vars->id_entry);
//
//            //condition
//            if ($vars->is_image === true)
//            {
//                //vars
//                $res        = FileTools::get_image_data($file_path);
//                $mime_type  = $res['mime_type'];
//                $img_width  = $res['width'];
//                $img_height = $res['height'];
//            }
//            else
//            {
//                //vars
//                $mime_type  = FileTools::get_mime($file_path);
//                $img_width  = null;
//                $img_height = null;
//            }
//
//            //vars
//            $binds_i[] = array(
//                'id_entry'   => $vars->id_entry,
//                'name'       => $v['file_name'],
//                'file_size'  => filesize($file_path),
//                'mime_type'  => $mime_type,
//                'is_image'   => $vars->is_image ? 1 : 0,
//                'img_width'  => $img_width,
//                'img_height' => $img_height,
//            );
//        }
//
//        //condition
//        if ($binds_i)
//        {
//            //query -> execute
//            $this->sql->prepare(array(
//                'query' => '
//                    INSERT INTO '.$this->sql->queryTable($sql_table).'
//                        (id_entry, name, file_size, mime_type, is_image, img_width, img_height)
//                    VALUES
//                        (:id_entry, :name, :file_size, :mime_type, :is_image, :img_width, :img_height)',
//                'binds' => $binds_i
//            ));
//        }
//
//        //query -> prepare
//        $this->sql->prepare(array(
//            'query' => '
//                UPDATE '.$this->sql->queryTable($sql_table).' SET
//                    sequence = :sequence
//                WHERE
//                        id_entry = :id_entry
//                    AND name = :name',
//            'binds' => $binds_s
//        ));
//    }
//
//    public function imagesContentUpdate($vars)
//    {
//        //vars
//        $vars = (object) array_merge(array(
//            'section'  => null,
//            'id_entry' => null,
//            'images'   => [], //[file_name_temp, file_name, file_source]
//            'content'  => null
//        ), $vars);
//
//        //condition
//        if ($vars->images)
//        {
//            //init vars
//            $search  = [];
//            $replace = [];
//
//            //loop
//            foreach ($vars->images as $v)
//            {
//                //vars
//                $search[]  = $this->cfg->get('link.site').$this->cfg->get('folder.temp').'/'.$v['file_name_temp'];
//                $replace[] = $this->get_file_link($v['file_name'], $vars->section, $vars->id_entry);
//            }
//
//            //return
//            return str_replace($search, $replace, $vars->content);
//        }
//
//        //return
//        return $vars->content;
//    }
//
//    public function load($vars)
//    {
//        //vars
//        $vars = (object) array_merge(array(
//            'section'  => null,
//            'id_entry' => 0
//        ), $vars);
//
//        //vars
//        $sql_table = $vars->section.'_image';
//
//        //query -> param
//        $q_param = array(
//            'table'     => $sql_table,
//            'field'     => 'name, info AS caption',
//            'where'     => array(
//                'id_entry = :id_entry',
//            ),
//            'bind'      => array(
//                'id_entry' => $vars->id_entry,
//            ),
//            'order'     => 'sequence ASC'
//        );
//
//        //query -> select
//        $sql_res = $this->sql->select($q_param);
//
//        //return
//        return $sql_res;
//    }

/*

    static public function replace($content, $section)
        {
            //vars ::: _sql
            $_sql = sql_init();

            //procedure ::: extract shortcuts
            preg_match_all('/{image=(.*?)\[(.*?)\]}/is', $content, $shortcuts);

            //return content if no shortcuts are found
            if (empty($shortcuts[1]))
                return $content;

            //default vars
            $images_ids = [];
            $images     = [];

            //procedure ::: parse shortcuts
            foreach ($shortcuts[1] as $shortcut_key => $image_id)
                {
                    $images_ids[] = $image_id;
                }

            //select -> {section}_image
            $_sql->params['table']  = $_sql->table($section.'_image');
            $_sql->params['fields'] = 'id, image';
            $_sql->params['where']  = 'id IN ('.implode(',', $images_ids).')';

            $sql_res = $_sql->select();

            foreach ($sql_res as $sql_row)
                {
                    extract($sql_row);

                    $images[$id] = $image;
                }

            // + default vars
            $_search  = [];
            $_replace = [];
            $sr       = 0;

            //procedure ::: parse shortcuts
            foreach ($shortcuts[1] as $shortcut_key => $image_id)
                {
                    $sr++;

                    $_search[$sr] = $shortcuts[0][$shortcut_key];

                    if (isset($images[$image_id]))
                        {
                            //vars ::: image_attr
                            $image_attr            = [];
                            $image_attr['align']   = 'center';
                            $image_attr['type']    = 'default';
                            $image_attr['caption'] = null;

                            $image_params = explode(', ', $shortcuts[2][$shortcut_key]);

                            foreach ($image_params as $attr)
                                {
                                    $attr_info = explode('=', $attr);

                                    $image_attr[trim($attr_info[0])] = trim($attr_info[1]);
                                }

                            //vars ::: image code
                            $image_code  = '<img src="'.self::show_image($images[$image_id], $section, $image_attr['type']).'"';
                            $image_code .= isset($image_attr['width']) ? ' width="'.$image_attr['width'].'"' : null;
                            $image_code .= isset($image_attr['height']) ? ' width="'.$image_attr['height'].'"' : null;
                            $image_code .= ' alt="'.$image_attr['caption'].'" itemprop="image" />';

                            //vars ::: image html
                            switch ($image_attr['align'])
                                {
                                    case 'left':
                                        $_replace[$sr] = '<figure class="image_left">'.$image_code.'<figcaption>'.$image_attr['caption'].'</figcaption></figure>';
                                    break;

                                    case 'right':
                                        $_replace[$sr] = '<figure class="image_right">'.$image_code.'<figcaption>'.$image_attr['caption'].'</figcaption></figure>';
                                    break;

                                    default:
                                        $_replace[$sr] = '<figure class="image_center" align="center">'.$image_code.'<figcaption>'.$image_attr['caption'].'</figure>';
                                }

                        }
                    else
                        {
                            $_replace[$sr] = null;
                        }
                }

            //procedure ::: replace images
            $content = str_replace($_search, $_replace, $content);

            //return
            return $content;
        }
*/
