<?php

namespace App\Images;

/**
 * Image Processing
 *
 * @author duy
 */
class ImageProcessing {
    /*     * *
     * Get the image path containing images of bread
     */

    public static function resizeImage($fileName, $height) {
        $output = `convert $fileName -resize $height $fileName`;
    }

    /**
     * Process a photo
     * @param type $photo
     */
    public static function processPhoto($photo, $imagePath) {
        $output = null;
        if (!empty($photo)) {

            // output image file name (usually in public directory under img/pains)
            $fileName = $imagePath . '/' . $photo->getClientOriginalName();
            $output = str_replace(" ", "_", $fileName) . "." . date("Y_m_d_G_i");
            $photo->move($imagePath, $output);
            //echo "<br>" . Pain::getImagePath() . $photo1->getClientOriginalName();
            // scale image to 200 px
//            ImageProcessing::resizeImage($output, 200);
        }

        return $output;
    }

    /**
     * Process a photo and resize
     * @param type $photo
     */
    public static function processPhotoAndResize($photo, $imagePath, $imgWidth, $id) {
        $output = null;
        if (!empty($photo)) {
            //echo 'Avatar is not empty';
            // output image file name (usually in public directory under img/pains)
            $fileName = $imagePath . '/' . $photo->getClientOriginalName();

            $output = str_replace(" ", "_", $fileName) . "." . date("Y_m_d_G_i") . "_" . $id;
            $photo->move($imagePath, $output);
            //echo "<br>" . Pain::getImagePath() . $photo1->getClientOriginalName();
            // scale image to width px
//            ImageProcessing::resizeImage($output, $imgWidth);
        }


        if ($output[0] != '/') {
            $output = '/' . $output;
        }

        return $output;
    }

}
