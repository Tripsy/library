<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

class ImagickCustom
{
    function __construct($source_path)
    {
        $this->source_path = $source_path;
        $this->image_details = $this->_image_details($this->source_path);
        $this->image_source = $this->_image_source($this->source_path);

        $this->params['adj_x'] = 0;
        $this->params['adj_y'] = 0;
    }

    public function cropThumbnailImage($width, $height)
    {
        /*
        //conditions - set in case one of the original image size are smaller than curent one
        $width = ($this->image_details['width'] > $width) ? $width : $this->image_details['width'];
        $height = ($this->image_details['height'] > $height) ? $height : $this->image_details['height'];
        */

        //comparison equation
        $new_eq = $width / $height;
        $old_eq = $this->image_details['width'] / $this->image_details['height'];

        //find thumb sizes
        if ($old_eq >= $new_eq) //original proportion is bigger than desired
        {
            $this->params['width'] = $width;
            $this->params['height'] = round(1 / $old_eq * $width);
        } elseif ($old_eq < $new_eq) //original proportion is smaller than desired
        {
            $this->params['width'] = round($old_eq * $height);
            $this->params['height'] = $height;
        } else //elseif($old_eq == 1)
        {
            $this->params['width'] = $new_eq >= 1 ? $height : $width;
            $this->params['height'] = $this->params['width'];
        }

        //crop adjustments & update thumb sizes to match "exact" settings
        if ($width > $this->params['width']) {
            $temp_thumb_h = $width / $this->params['width'] * $this->params['height'];
            $old_y_c = ($temp_thumb_h - $height) * $this->image_details['height'] / $temp_thumb_h;
            $this->params['adj_y'] = round($old_y_c / 2);
            $this->image_details['height'] = round($this->image_details['height'] - $old_y_c);
            $this->params['width'] = $width;
        }

        if ($height > $this->params['height']) {
            $temp_thumb_w = $height / $this->params['height'] * $this->params['width'];
            $old_x_c = ($temp_thumb_w - $width) * $this->image_details['width'] / $temp_thumb_w;
            $this->params['adj_x'] = round($old_x_c / 2);
            $this->image_details['width'] = round($this->image_details['width'] - $old_x_c);
            $this->params['height'] = $height;
        }
    }

    public function scaleImage($width, $height)
    {
        if ($width && !$height) {
            $this->params['width'] = $width;
            $this->params['height'] = round($this->image_details['height'] / $this->image_details['width'] * $width);
        } elseif (!$width && $height) {
            $this->params['width'] = round($this->image_details['width'] / $this->image_details['height'] * $height);
            $this->params['height'] = $height;
        } else {
            $this->cropThumbnailImage($width, $height);
        }
    }

    public function cropImage($width, $height, $adj_x, $adj_y)
    {
        $this->params['width'] = $width;
        $this->params['height'] = $height;
        $this->params['adj_x'] = $adj_x;
        $this->params['adj_y'] = $adj_y;

        $this->image_details['width'] = $width;
        $this->image_details['height'] = $height;
    }

    public function writeImage($thumb_path)
    {
        //vars
        $dst_img = ImageCreateTrueColor($this->params['width'], $this->params['height']);

        //case
        switch ($this->image_details['extension']) {
            case 'gif':
                //transparency tweeks
                $trnprt_indx = imagecolortransparent($this->image_source);

                if ($trnprt_indx >= 0) {
                    $trnprt_color = imagecolorsforindex($this->image_source, $trnprt_indx);
                    $trnprt_indx = imagecolorallocate($dst_img, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                    imagefill($dst_img, 0, 0, $trnprt_indx);
                    imagecolortransparent($dst_img, $trnprt_indx);
                }

                imagecopyresampled($dst_img, $this->image_source, 0, 0, $this->params['adj_x'], $this->params['adj_y'], $this->params['width'], $this->params['height'], $this->image_details['width'], $this->image_details['height']);
                imagegif($dst_img, $thumb_path);
                break;

            case 'jpg':
                imagecopyresampled($dst_img, $this->image_source, 0, 0, $this->params['adj_x'], $this->params['adj_y'], $this->params['width'], $this->params['height'], $this->image_details['width'], $this->image_details['height']);
                imagejpeg($dst_img, $thumb_path);
                break;

            case 'png':
                //transparency tweeks
                imagealphablending($dst_img, false);
                imagesavealpha($dst_img, true);

                imagecopyresampled($dst_img, $this->image_source, 0, 0, $this->params['adj_x'], $this->params['adj_y'], $this->params['width'], $this->params['height'], $this->image_details['width'], $this->image_details['height']);
                imagepng($dst_img, $thumb_path);
                break;

            case 'bmp':
                imagecopyresampled($dst_img, $this->image_source, 0, 0, $this->params['adj_x'], $this->params['adj_y'], $this->params['width'], $this->params['height'], $this->image_details['width'], $this->image_details['height']);
                imagewbmp($dst_img, $thumb_path);
                break;
        }

        //execute ::: destroy image => free up memory
        imagedestroy($dst_img);
    }

    public function rotateImage($color, $angle)
    {
        imagealphablending($this->image_source, false);
        imagesavealpha($this->image_source, true);

        $rotation = imagerotate($this->image_source, $angle, imageColorAllocateAlpha($this->image_source, 0, 0, 0, 127));

        //case
        switch ($this->image_details['extension']) {
            case 'gif':
                //transparency tweeks
                $trnprt_indx = imagecolortransparent($this->image_source);

                if ($trnprt_indx >= 0) {
                    $trnprt_color = imagecolorsforindex($this->image_source, $trnprt_indx);
                    $trnprt_indx = imagecolorallocate($rotation, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                    imagefill($rotation, 0, 0, $trnprt_indx);
                    imagecolortransparent($rotation, $trnprt_indx);
                }

                imagegif($rotation, $this->source_path);
                break;

            case 'jpg':
                imagejpeg($rotation, $this->source_path);
                break;

            case 'png':
                //transparency tweeks
                imagealphablending($rotation, false);
                imagesavealpha($rotation, true);

                imagepng($rotation, $this->source_path);
                break;

            case 'bmp':
                imagewbmp($rotation, $this->source_path);
                break;
        }

        //execute ::: destroy image => free up memory
        imagedestroy($rotation);
    }

    private function _image_details($image_path): array
    {
        //default vars
        $image_details = [];

        //vars ::: image_attr
        $image_attr = getimagesize($image_path);

        //vars ::: image_details -> width, height, size
        $image_details['width'] = $image_attr[0];
        $image_details['height'] = $image_attr[1];
        $image_details['size'] = $image_attr[0] . 'x' . $image_attr[1];

        //vars ::: image_details -> extension
        switch ($image_attr[2]) {
            case 1:
                $image_details['extension'] = 'gif';
                break;

            case 2:
                $image_details['extension'] = 'jpg';
                break;

            case 3:
                $image_details['extension'] = 'png';
                break;

            case 6:
                $image_details['extension'] = 'bmp';
                break;

            default:
                $image_details['extension'] = null;
        }

        //return
        return $image_details;
    }

    private function _image_source($image_path)
    {
        //case
        switch ($this->image_details['extension']) {
            case 'gif':
                return imagecreatefromgif($image_path);
                break;

            case 'jpg':
                return imagecreatefromjpeg($image_path);
                break;

            case 'png':
                return imagecreatefrompng($image_path);
                break;

            case 'bmp':
                return imagecreatefromwbmp($image_path);
                break;
        }
    }
}

?>
