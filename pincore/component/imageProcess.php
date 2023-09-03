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

class ImageProcess
{

    /*
     * resize image
     * $img = path image
     * $pathNewImg = path for save new image resize
     * $w = width if "auto" get width by height
     * $h = height if "auto" get height by width
     * $fix = if true get width and height by each Whichever is larger
     *
     * example1 : imageResize("img","newImg",100,100);
     * ^->>> resize image with width : 100 and height : 100
     *
     * example2 : imageResize("img","newImg","auto",100);
     * ^->>> resize image with width : auto(byHeight) and height : 100
     *
     * example3 : imageResize("img","newImg",100,"auto");
     * ^->>> resize image with width : 100 and height : auto(byWidth)
     *
     * example4 : imageResize("img","newImg",150,300,true);
     * ^->>> resize image with width : auto(byHeight) and height : 300 because height > width
     */
    public static function resize($img, $pathNewImg, $w = 100, $h = 100, $fix = false)
    {
        //Check if GD extension is loaded
        if (!extension_loaded('gd') && !extension_loaded('gd2')) {
            trigger_error("GD is not loaded", E_USER_WARNING);
            return false;
        }

        //Get Image size info
        $imgInfo = getimagesize($img);
        switch ($imgInfo[2]) {
            case 1:
                $im = imagecreatefromgif($img);
                break;
            case 2:
                $im = imagecreatefromjpeg($img);
                break;
            case 3:
                $im = imagecreatefrompng($img);
                break;
            case 18:
                $im = imagecreatefromwebp($img);
                break;
            default:
                trigger_error('Unsupported filetype!', E_USER_WARNING);
                break;
        }

        //yeah, resize it, but keep it proportional
        $orig_width = imagesx($im);
        $orig_height = imagesy($im);
        $nWidth = $w;
        $nHeight = $h;
        if ($w == 'auto') {
            $nWidth = (($orig_width * $nHeight) / $orig_height);
        } else if ($h == 'auto') {
            $nHeight = (($orig_height * $nWidth) / $orig_width);
        } else if ($fix) {
            if ($orig_height < $orig_width) {
                $nHeight = (($orig_height * $nWidth) / $orig_width);

            } else if ($orig_height > $orig_width) {
                $nWidth = (($orig_width * $nHeight) / $orig_height);
            }
        }
        $nWidth = round($nWidth);
        $nHeight = round($nHeight);

        $newImg = imagecreatetruecolor($nWidth, $nHeight);

        /* Check if this image is PNG or GIF, then set if Transparent*/
        if (($imgInfo[2] == 1) or ($imgInfo[2] == 3) or ($imgInfo[2] == 18)) {
            imagealphablending($newImg, false);
            imagesavealpha($newImg, true);
            $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
            imagefilledrectangle($newImg, 0, 0, $nWidth, $nHeight, $transparent);
        }
        imagecopyresampled($newImg, $im, 0, 0, 0, 0, $nWidth, $nHeight, $imgInfo[0], $imgInfo[1]);

        //Generate the file, and rename it to $newfilename
        switch ($imgInfo[2]) {
            case 1:
                imagegif($newImg, $pathNewImg);
                break;
            case 2:
                imagejpeg($newImg, $pathNewImg);
                break;
            case 3:
                imagepng($newImg, $pathNewImg);
                break;
            case 18:
                imagewebp($newImg, $pathNewImg);
                break;
            default:
                trigger_error('Failed resize image!', E_USER_WARNING);
                break;
        }

        return $pathNewImg;
    }


    /*
     * watermark image
     * $img = path image
     * $logo_path = path for logo watermark
     * $h_logo = height print logo on image or can type "top" or "down" or "center"
     * $w_logo = width print logo on image or can type "right" or "left" or "center"
     * $div_photo = count div for width and height
     *
     * $div_photo = 2
     * (0,0)  (0,1)  (0,2)
     * (1,0)  (1,1)  (1,2)
     * (2,0)  (2,1)  (2,2)
     *
     *
     * @ @ @
     * @ + @
     * @ @ @
     * example1 : for print logo in "center"
     * ^->>> watermark("img.jpg","logoimg.png",1,1)
     * or
     * ^->>> watermark("img.jpg","logoimg.png","center","center")
     *
     *
     * @ @ +
     * @ @ @
     * @ @ @
     * example2 : for print logo in "top and right"
     * ^->>> watermark("img.jpg","logoimg.png",0,2)
     * or
     * ^->>> watermark("img.jpg","logoimg.png","top","right")
     *
     *
     * + @ @
     * @ @ @
     * @ @ @
     * example3 : for print logo in "top and left"
     * ^->>> watermark("img.jpg","logoimg.png",0,0)
     * or
     * ^->>> watermark("img.jpg","logoimg.png","top","left")
     *
     *
     * @ + @
     * @ @ @
     * @ @ @
     * example4 : for print logo in "top and center"
     * ^->>> watermark("img.jpg","logoimg.png",0,1)
     * or
     * ^->>> watermark("img.jpg","logoimg.png","top","center")
     *
     *
     * @ @ @
     * @ @ @
     * @ @ +
     * example5 : for print logo in "down and right"
     * ^->>> watermark("img.jpg","logoimg.png",2,2)
     *or
     * ^->>> watermark("img.jpg","logoimg.png","down","right")
     *
     *
     * @ @ @
     * @ @ @
     * + @ @
     * example6 : for print logo in "down and left"
     * ^->>> watermark("img.jpg","logoimg.png",2,0)
     * or
     * ^->>> watermark("img.jpg","logoimg.png","down","left")
     *
     *
     * @ @ @
     * + @ @
     * @ @ @
     * example7 : for print logo in "center and left"
     * ^->>> watermark("img.jpg","logoimg.png",1,0)
     * or
     * ^->>> watermark("img.jpg","logoimg.png","center","left")
     *
     * or To be more precise  more to $div_photo for example = 100
     *
     */
    public static function watermark($img, $logo_path = false, $h_logo = 1, $w_logo = 1, $div_photo = 2)
    {
        #is this file really exsits ?
        if (!file_exists($img)) {
            return;
        }

        if (!is_int($h_logo)) {
            $h_logo = strtolower($h_logo);
            switch ($h_logo) {
                case 'top':
                    $h_logo = 0;
                    break;
                case 'center':
                    $h_logo = $div_photo / 2;
                    break;
                case 'down':
                    $h_logo = $div_photo;
                    break;
                default :
                    $h_logo = $div_photo / 2;
            }
        }

        if (!is_int($w_logo)) {
            $w_logo = strtolower($w_logo);
            switch ($w_logo) {
                case 'left':
                    $w_logo = 0;
                    break;
                case 'center':
                    $w_logo = $div_photo / 2;
                    break;
                case 'right':
                    $w_logo = $div_photo;
                    break;
                default :
                    $w_logo = $div_photo / 2;
            }
        }

        $ext = File::extension($img);
        $ext_logo = File::extension($logo_path);
        $src_logo = false;

        #check size logo
        $h_logo = ($h_logo > $div_photo) ? $div_photo : $h_logo;
        $h_logo = ($h_logo < 0) ? 0 : $h_logo;
        $w_logo = ($w_logo > $div_photo) ? $div_photo : $w_logo;
        $w_logo = ($w_logo < 0) ? 0 : $w_logo;

        if (file_exists($logo_path)) {
            if ($ext_logo == 'png')
                $src_logo = imagecreatefrompng($logo_path);
            else if ($ext_logo == 'gif')
                $src_logo = imagecreatefromgif($logo_path);
            else if ($ext_logo == 'jpg' || $ext_logo == 'jpeg')
                $src_logo = imagecreatefromjpeg($logo_path);
        }


        #no watermark pic
        if (!$src_logo) {
            return;
        }

        #if there is imagick lib, then we should use it
        if (function_exists('phpversion') && phpversion('imagick')) {
            self::watermark_imagick($img, $ext, $logo_path, $w_logo, $h_logo, $div_photo);
            return;
        }

        #now, lets work and detect our image extension
        if (strpos($ext, 'jpg') !== false || strpos($ext, 'jpeg') !== false) {
            $src_img = @imagecreatefromjpeg($img);
        } elseif (strpos($ext, 'png') !== false) {
            $src_img = @imagecreatefrompng($img);
        }elseif (strpos($ext, 'webp') !== false) {
            $src_img = @imagecreatefromwebp($img);
        } elseif (strpos($ext, 'gif') !== false) {
            return;
            $src_img = @imagecreatefromgif($img);
        } else {
            return;
        }

        #detect width, height for the image
        $bwidth = @imageSX($src_img);
        $bheight = @imageSY($src_img);

        #detect width, height for the watermark image
        $lwidth = @imageSX($src_logo);
        $lheight = @imageSY($src_logo);


        if ($bwidth > $lwidth + 5 && $bheight > $lheight + 5) {
            #where exaxtly do we have to make the watermark ..
            $src_x = (($bwidth - ($lwidth + 5)) / $div_photo) * $w_logo;
            $src_y = (($bheight - ($lheight + 5)) / $div_photo) * $h_logo;

            #make it now, watermark it
            @ImageAlphaBlending($src_img, true);
            @ImageCopy($src_img, $src_logo, $src_x, $src_y, 0, 0, $lwidth, $lheight);

            if (strpos($ext, 'jpg') !== false || strpos($ext, 'jpeg') !== false) {
                @imagejpeg($src_img, $img);
            } elseif (strpos($ext, 'png') !== false) {
                @imagepng($src_img, $img);
            }elseif (strpos($ext, 'webp') !== false) {
                @imagewebp($src_img, $img);
            } elseif (strpos($ext, 'gif') !== false) {
                @imagegif($src_img, $img);
            } elseif (strpos($ext, 'bmp') !== false) {
                @imagebmp($src_img, $img);
            }
        } else {
            #image is not big enough to watermark it
            return false;
        }
        return true;
    }


#
# generate watermarked images by imagick
#
    private function watermark_imagick($name, $ext, $logo, $w_logo, $h_logo, $div_photo)
    {
        #Not just me babe, All the places mises you ..
        $im = new \Imagick($name);

        $watermark = new \Imagick($logo);
        //$watermark->readImage($);

        #how big are the images?
        $iWidth = $im->getImageWidth();
        $iHeight = $im->getImageHeight();
        $wWidth = $watermark->getImageWidth();
        $wHeight = $watermark->getImageHeight();

        if ($iHeight < $wHeight || $iWidth < $wWidth) {
            #resize the watermark
            $watermark->scaleImage($iWidth, $iHeight);

            #get new size
            $wWidth = $watermark->getImageWidth();
            $wHeight = $watermark->getImageHeight();
        }

        #calculate the position
        $x = (($iWidth - ($wWidth - 5)) / $div_photo) * $w_logo;
        $y = (($iHeight - ($wHeight - 5)) / $div_photo) * $h_logo;

        #an exception for gif image
        #generating thumb with 10 frames only, big gif is a devil
        $composite_over = \imagick::COMPOSITE_OVER;

        if ($ext == 'gif') {
            $i = 0;
            //$gif_new = new Imagick();
            foreach ($im as $frame) {
                $frame->compositeImage($watermark, $composite_over, $x, $y);

                //	$gif_new->addImage($frame->getImage());
                if ($i >= 10) {
                    # more than 10 frames, quit it
                    break;
                }
                $i++;
            }
            $im->writeImages($name, true);
            return;
        }

        $im->compositeImage($watermark, $composite_over, $x, $y);

        $im->writeImages($name, false);
    }

    /*
     * Convert Image
     * Supports them: JPEG, PNG, GIF, BMP.
     * need GD library
     */
    public static function converter($image, $convert_type = 'png', $img_save = null, $is_old_delete = false, $is_no_self = false)
    {
        $convert_type = strtolower($convert_type);

        // return if null file
        if (!is_file($image)) return false;

        // return if ext image == $convert_type
        if ($is_no_self) {
            $ext = File::extension($image);
            if ($ext == $convert_type) return false;
        }

        // get location for new image
        $img_save = (empty($img_save)) ? $image : $img_save;
        $img_save = HelperString::deleteExt($img_save, 3, 4);
        $img_save = $img_save . '.' . $convert_type;
        $getImage = imagecreatefromstring(file_get_contents($image));

        // create folder if null location dir for save new image
        File::make_folder(File::dir($img_save), true);

        // Convert Image
        switch ($convert_type) {
            case 'png':
                $return = @imagepng($getImage, $img_save);
                break;
            case 'webp':
                $return = @imagewebp($getImage, $img_save);
                break;
            case 'jpg':
                $return = @imagejpeg($getImage, $img_save);
                break;
            case 'jpeg':
                $return = @imagejpeg($getImage, $img_save);
                break;
            case 'gif':
                $return = @imagegif($getImage, $img_save);
                break;
            case 'bmp':
                $return = @imagebmp($getImage, $img_save);
                break;
            default:
                $return = false;
                break;
        }

        if ($is_old_delete) File::remove($image);

        return $return;
    }
}