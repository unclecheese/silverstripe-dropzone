<?php

/**
 * Track files as they're uploaded and remove when they've been saved.
 * NOTE: Run task to cleanup leftover files.
 *
 * @package  unclecheese/silverstripe-dropzone
 */
class FileAttachmentFieldTrack extends DataObject {
    private static $db = array(
        'SessionID' => 'Varchar(255)',
        'FormClass' => 'Varchar(60)',
    );

    private static $has_one = array(
        'File' => 'File',
        'Page' => 'SiteTree',
    );

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if (!$this->exists()) {
            $this->SessionID = session_id();

            // Store page this file was tracked on.
            if (Controller::has_curr()) {
                $controller = Controller::curr();
                if ($controller->hasMethod('data')) {
                    $pageRecord = $controller->data();
                    if ($pageRecord && $pageRecord instanceof DataObjectInterface) {
                        $this->PageID = $pageRecord->ID;
                    }
                }
            }
        }
    }
}
