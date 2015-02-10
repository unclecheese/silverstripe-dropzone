<?php

/**
 * Adds helper methods to the core {@link File} object
 *
 * @package  unclecheese/dropzone
 * @author  Uncle Cheese <unclecheese@leftandmain.com>
 */
class DropzoneFile extends DataExtension {


	/**
	 * Helper method for determining if this is an Image
	 *
	 * @return  boolean
	 */
	public function IsImage() {
		return $this->owner instanceof Image;
	}


	/**
	 * Gets a thumbnail for this file given a size. If it's an Image,
	 * it will render the actual file. If not, it will provide an icon based
	 * on the extension.
	 * @param  int $w The width of the image
	 * @param  int $h The height of the image
	 * @return Image_Cached
	 */
	public function getPreviewThumbnail($w = null, $h = null) {
		if(!$w) $w = $this->owner->config()->grid_thumbnail_width;
		if(!$h) $h = $this->owner->config()->grid_thumbnail_height;

		if($this->IsImage()) {
			return $this->owner->CroppedImage($w, $h);
		}

        foreach(Config::inst()->forClass('FileAttachmentField')->icon_sizes as $size) {
            if($w <= $size) {
            	$file = $this->getFilenameForExtension($this->owner->getExtension(), $size);
				if(!file_exists(BASE_PATH.'/'.$file)) {
					$file = $this->getFilenameForExtension('_blank', $size);
				}

				return new Image_Cached(Director::makeRelative($file));
            }
        }
	}	


	/**
	 * Gets a filename based on the extension and the size
	 * 
	 * @param  string $ext  The extension of the file, e.g. "pdf"
	 * @param  int $size The size of the image
	 * @return string
	 */
	protected function getFilenameForExtension($ext, $size) {
		return sprintf(
					'%s/images/file-icons/%spx/%s.png',
					DROPZONE_DIR,
					$size,
					strtolower($ext)
				);
	}
}