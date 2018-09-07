<?php
/**
 * File class handles file uploads.  All functions are static.
 * @author misterhaan
 */
class t7file {
	/**
	 * finds the correct file extension for an uploaded image.
	 * @param array $upload the part of $_FILES that contains information on the uploaded image.
	 * @return string|boolean image file extension, or false if not jpeg or png.
	 */
	public static function GetImageExtension($upload) {
		switch(getimagesize($upload['tmp_name'])[2]) {
			case IMAGETYPE_JPEG: return 'jpeg';
			case IMAGETYPE_PNG:  return 'png';
			default:             return false;
		}
	}

	/**
	 * save an uploaded image after resizing to fit a maximum size.
	 * @param array $upload the part of $_FILES that contains information on the uploaded image.
	 * @param string $type type of uploaded image; can be png, jpeg, or jpg.
	 * @param array $dests destinations to save, names are full file paths and values are maximum size.
	 * @param array|boolean $exif if passed, the image will be rotated if exif data says it should be.
	 */
	public static function SaveUploadedImage($upload, $type, $dests, $exif = false) {
		$size = getimagesize($upload['tmp_name']);
		$image = self::ReadImageFile($upload['tmp_name'], $type);
		unlink($upload['tmp_name']);
		if($exif)
			$image = self::AutoRotateImage($image, $exif, $size);
		foreach($dests as $filename => $max)
			self::SaveResizedImage($image, $type, $filename, $size[0], $size[1], $max);
	}

	private static function SaveResizedImage($image, $type, $filename, $width, $height, $max) {
		if($width > $max || $height > $max){
			$aspect = $width / $height;
			if($aspect > 1) {
				$w = $max;
				$h = round($max / $aspect);
			} else {
				$h = $max;
				$w = round($max * $aspect);
			}
		} else {
			$w = $width;
			$h = $height;
		}
		$resized = imagecreatetruecolor($w, $h);
		if($type == 'png') {
			imagealphablending($resized, false);
			imagesavealpha($resized, true);
		}
		imagecopyresampled($resized, $image, 0, 0, 0, 0, $w, $h, $width, $height);
		self::SaveImageFile($resized, $filename, $type);
		imagedestroy($resized);
	}

	private static function ReadImageFile($filename, $type) {
		switch($type) {
			case 'png':
				return imagecreatefrompng($filename);
			case 'jpeg':
			case 'jpg':
				return imagecreatefromjpeg($filename);
		}
		return null;
	}

	private static function SaveImageFile($image, $filename, $type) {
		switch($type) {
			case 'png':
				return imagepng($image, $filename);
			case 'jpeg':
			case 'jpg':
				return imagejpeg($image, $filename);
		}
	}

	private static function AutoRotateImage($image, $exif, &$size) {
		if(isset($exif['IFD0']['Orientation']))
			switch($exif['IFD0']['Orientation']) {
				case 3:
					return imagerotate($image, 180, 0);
				case 6:
					$tmp = $size[0];
					$size[0] = $size[1];
					$size[1] = $tmp;
					return imagerotate($image, -90, 0);
				case 8:
					$tmp = $size[0];
					$size[0] = $size[1];
					$size[1] = $tmp;
					return imagerotate($image, 90, 0);
			}
	}
}