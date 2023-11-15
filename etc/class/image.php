<?php

class Image {
	private string $path;
	private mixed $exif = null;

	public function __construct($path) {
		$this->path = $path;
	}

	public static function FromUpload(string $name): ?Image {
		if (!isset($_FILES[$name]) || !$_FILES[$name]['size'])
			return null;
		return new Image($_FILES[$name]['tmp_name']);
	}

	public function GetEXIF(): array|false {
		if ($this->exif == null)
			$this->exif = exif_read_data($this->path, 'EXIF', true);
		return $this->exif;
	}

	public function SaveResized(string $type, array $destMap, bool $cropSquare = false): void {
		$size = getimagesize($this->path);
		$image = $this->ReadImageFile($type);
		if (!$image)
			throw new DetailedException('unable to read image file', $this->path);
		$exif = $this->GetEXIF();
		if ($exif)
			$image = self::AutoRotateImage($image, $exif, $size);
		foreach ($destMap as $filename => $maxPixels) {
			if (is_numeric($filename) && !is_numeric($maxPixels)) {
				$filename = $maxPixels;
				$maxPixels = 0;
			}
			if ($cropSquare)
				self::SaveResizedSquareImage($image, $type, $filename, $size[0], $size[1], $maxPixels);
			else
				self::SaveResizedImage($image, $type, $filename, $size[0], $size[1], $maxPixels);
		}
	}

	public function Delete(): void {
		unlink($this->path);
	}

	private function ReadImageFile(string $type): ?GdImage {
		switch ($type) {
			case 'png':
				return imagecreatefrompng($this->path);
			case 'jpeg':
			case 'jpg':
				return imagecreatefromjpeg($this->path);
		}
		return null;
	}

	private function AutoRotateImage(GdImage $image, array $exif, array $size): GdImage {
		if (isset($exif['IFD0']['Orientation']))
			switch ($exif['IFD0']['Orientation']) {
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
		return $image;
	}

	private static function SaveResizedImage(GdImage $image, string $type, string $filename, int $width, int $height, int $max): void {
		if ($max && ($width > $max || $height > $max)) {
			$aspect = $width / $height;
			if ($aspect > 1) {
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
		self::SaveCroppedResizedImage($image, $type, $filename, $w, $h, $width, $height);
	}

	private static function SaveResizedSquareImage(GdImage $image, string $type, string $filename, int $width, int $height, int $max): void {
		$size = min($width, $height);
		$left = $width > $height ? round(($width - $height) / 2) : 0;
		$top = $height > $width ? round(($height - $width) / 2) : 0;
		$s = min($size, $max);
		self::SaveCroppedResizedImage($image, $type, $filename, $s, $s, $size, $size, $left, $top);
	}

	private static function SaveCroppedResizedImage(GdImage $image, string $type, string $filename, int $width, int $height, int $srcWidth, int $srcHeight, int $left = 0, int $top = 0): void {
		$resized = imagecreatetruecolor($width, $height);
		if ($type == 'png') {
			imagealphablending($resized, false);
			imagesavealpha($resized, true);
		}
		imagecopyresampled($resized, $image, 0, 0, $left, $top, $width, $height, $srcWidth, $srcHeight);
		self::SaveImageFile($resized, $filename, $type);
		imagedestroy($resized);
	}

	private static function SaveImageFile(GdImage $image, string $filename, string $type): bool {
		switch ($type) {
			case 'png':
				return imagepng($image, $filename);
			case 'jpeg':
			case 'jpg':
				return imagejpeg($image, $filename);
		}
	}
}
