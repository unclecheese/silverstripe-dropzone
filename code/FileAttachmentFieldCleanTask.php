<?php

/**
 * Delete all files being tracked that weren't saved against anything.
 *
 * @package  unclecheese/silverstripe-dropzone
 */
class FileAttachmentFieldCleanTask extends BuildTask {
    protected $title = "File Attachment Field - Clear tracked files";
    
    protected $description = 'Delete files uploaded via FileAttachmentField that aren\'t attached to anything';
    
    public function run($request) {
        $files = FileAttachmentFieldTrack::get()->filter(array('Created:LessThanOrEqual' => date('Y-m-d H:i:s', time()-3600)));
        $files = $files->toArray();
        if ($files) {
            foreach ($files as $trackRecord) {
                $file = $trackRecord->File();
                if ($file && $file->exists()) {
                    DB::alteration_message('Remove File #'.$file->ID.' from "'.$trackRecord->FormClass.'" on Page #'.$trackRecord->PageID, 'changed');
                    $file->delete();
                }
                $trackRecord->delete();
            }
        } else {
            DB::alteration_message('No tracked files to remove.');
        }
    }
}
