<?php

namespace UncleCheese\Dropzone;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Track files as they're uploaded and remove when they've been saved.
 *
 * @package unclecheese/silverstripe-dropzone
 */
class FileAttachmentFieldTrack extends DataObject
{
    private static $db = array(
        'ControllerClass' => 'Varchar(60)',
        'RecordID' => 'Int',
        'RecordClass' => 'Varchar(60)',
    );

    private static $has_one = array(
        'File' => 'File',
    );

    private static $table_name = 'FileAttachmentFieldTrack';

    public static function untrack($fileIDs)
    {
        if (!$fileIDs) {
            return;
        }
        $fileIDs = (array)$fileIDs;
        $trackRecords = FileAttachmentFieldTrack::get()->filter(array('FileID' => $fileIDs));
        foreach ($trackRecords as $trackRecord) {
            $trackRecord->delete();
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->exists()) {
            // Store record this file was tracked on.
            if (!$this->RecordID && Controller::has_curr()) {
                $controller = Controller::curr();
                $pageRecord = null;
                if ($controller->hasMethod('data')) {
                    // Store page visiting on frontend (ContentController)
                    $pageRecord = $controller->data();
                } else if ($controller->hasMethod('currentPageID')) {
                    // Store editing page in CMS (LeftAndMain)
                    $id = $controller->currentPageID();
                    $pageRecord = $controller->getRecord($id);
                } else if ($controller->hasMethod('getRecord')) {
                    $pageRecord = $controller->getRecord();
                }

                if ($pageRecord && $pageRecord instanceof DataObjectInterface) {
                    $this->RecordID = $pageRecord->ID;
                    $this->RecordClass = $pageRecord->ClassName;
                }
            }
        }
    }

    public function setRecord($record)
    {
        $this->RecordID = $record->ID;
        $this->RecordClass = $record->ClassName;
    }

    public function Record()
    {
        if ($this->RecordClass && $this->RecordID) {
            return DataObject::get_one($this->RecordClass, "ID = ".(int)$this->RecordID);
        }
    }
}
