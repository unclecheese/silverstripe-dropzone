<?php

namespace UncleCheese\Dropzone;

use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\ORM\DataExtension;

/**
 * Adds helper methods to the core {@link File} object
 *
 * @package unclecheese/dropzone
 * @author  Uncle Cheese <unclecheese@leftandmain.com>
 */
class DropzoneFile extends DataExtension
{


    /**
     * Helper method for determining if this is an Image
     *
     * @return boolean
     */
    public function IsImage()
    {
        return $this->owner instanceof Image;
    }


    /**
     * Gets a thumbnail for this file given a size. If it's an Image,
     * it will render the actual file. If not, it will provide an icon based
     * on the extension.
     *
     * @param  int $w The width of the image
     * @param  int $h The height of the image
     * @return Image_Cached
     */
    public function getPreviewThumbnail($w = null, $h = null)
    {
        if(!$w) { $w = $this->owner->config()->grid_thumbnail_width;
        }
        if(!$h) { $h = $this->owner->config()->grid_thumbnail_height;
        }

        if($this->IsImage() && Director::fileExists($this->owner->Filename)) {
            return $this->owner->CroppedImage($w, $h);
        }

        $sizes = Config::inst()->forClass(FileAttachmentField::class)->icon_sizes;
        sort($sizes);

        foreach($sizes as $size) {
            if($w <= $size) {
                if($this->owner instanceof Folder) {
                    $file = $this->getFilenameForType('_folder', $size);
                }
                else {
                    $file = $this->getFilenameForType($this->owner->getExtension(), $size);
                }
                if(!file_exists(BASE_PATH.'/'.$file)) {
                    $file = $this->getFilenameForType('_blank', $size);
                }

                $image = Image::create();
                $image->setFromLocalFile(Director::getAbsFile($file), basename($file));

                return $image;
            }
        }
    }


    /**
     * Gets a filename based on the extension and the size
     *
     * @param  string $ext  The extension of the file, e.g. "pdf"
     * @param  int    $size The size of the image
     * @return string
     */
    protected function getFilenameForType($ext, $size)
    {
        return ModuleResourceLoader::singleton()->resolveResource(sprintf(
            'unclecheese/dropzone:images/file-icons/%spx/%s.png',
            $size,
            strtolower($ext)
        ));
    }
}