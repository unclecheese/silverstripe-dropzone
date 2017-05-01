<?php

/**
 * Defines the FileAttachementField form field type
 *
 * @package  unclecheese/silverstripe-dropzone
 * @author  Uncle Cheese <unclecheese@leftandmain.com>
 */
class FileAttachmentField extends FileField {

    /**
     * The allowed actions for the RequestHandler
     * @var array
     */
    private static $allowed_actions = array (
        'upload',
        'handleSelect',
    );


    private static $url_handlers = array (
        'select' => 'handleSelect',
    );

    /**
     * Track files that are uploaded and remove the tracked files when
     * they are saved into a record.
     *
     * @var boolean
     */
    private static $track_files = false;

    /**
     * A list of settings for this instance
     * @var array
     */
    protected $settings = array ();

    /**
     * Extra params to send to the server with the POST request
     * @var array
     */
    protected $params = array ();

    /**
     * The record that this FormField is editing
     * @var DataObject
     */
    protected $record = null;

    /**
     * A list of custom permissions for this instance
     * Options available:
     *  - upload
     *  - attach (select from existing)
     *  - detach (remove from record, but don't delete)
     *  -delete (delete from files)
     * @var array
     */
    protected $permissions = array ();

    /**
     * The style of uploader. Options: "grid", "list"
     * @var string
     */
    protected $view = 'list';

    /**
     * The preview template for uploaded files. Does not necessarily apply
     * to files that were on the record at load time, but rather to files
     * that have been attached to the uploader client side
     * @var string
     */
    protected $previewTemplate = 'FileAttachmentField_preview';

    /**
     * UploadField compatability. Used for the select handler, when KickAssets
     * is not installed
     * @var string
     */
    protected $displayFolderName;

    /**
     * Set to true if detected invalid file ID
     * @var boolean
     */
    protected $hasInvalidFileID;

    /**
     * Helper function to translate underscore_case to camelCase
     * @param  string $str
     * @return string
     */
    public static function camelise($str) {
        return preg_replace_callback('/_([a-z])/', function ($c) {
                return strtoupper($c[1]);
        }, $str);
    }

    /**
     * Translate camelCase to underscore_case
     * @param  string $str
     * @return string
     */
    public static function underscorise($str) {
        $str[0] = strtolower($str[0]);

        return preg_replace_callback('/([A-Z])/', function ($c) {
            return "_" . strtolower($c[1]);
        }, $str);
    }

    /**
     * Looks at the php.ini and takes the lower of two values, translates it into
     * an int representing the number of bytes allowed per upload
     *
     * @return int
     */
    public static function get_filesize_from_ini() {
        $bytes = min(array(
            File::ini2bytes(ini_get('post_max_size') ?: '8M'),
            File::ini2bytes(ini_get('upload_max_filesize') ?: '2M')
        ));

        return floor($bytes/(1024*1024));
    }

    /**
     * Constructor. Sets some default permissions
     * @param string $name
     * @param string $title
     * @param string $value
     * @param Form $form
     */
    public function __construct($name, $title = null, $value = null, $form = null) {
        $instance = $this;

        $this->permissions['upload'] = true;
        $this->permissions['detach'] = true;
        $this->permissions['delete'] = function () use ($instance) {
            return Injector::inst()->get('File')->canDelete() && $instance->isCMS();
        };
        $this->permissions['attach'] = function () use ($instance) {
            return $instance->isCMS();
        };

        parent::__construct($name, $title, $value, $form);
    }

    /**
     * Renders the form field, loads requirements. Sets file size based on php.ini
     * Adds the security token
     *
     * @param array $attributes
     * @return  SSViewer
     */
    public function FieldHolder($attributes = array ()) {
        $this->defineFieldHolderRequirements();
        return parent::FieldHolder($attributes);
    }

    /**
     * Renders the small form field holder, loads requirements. Sets file size based on php.ini
     * Adds the security token
     *
     * @param array $attributes
     * @return  SSViewer
     */
    public function SmallFieldHolder($attributes = array ()) {
        $this->defineFieldHolderRequirements();
        return parent::SmallFieldHolder($attributes);
    }

    /**
     * Define some requirements and settings just before rendering the Field Holder.
     */
    protected function defineFieldHolderRequirements() {
        Requirements::javascript(DROPZONE_DIR.'/javascript/dropzone.js');
        Requirements::javascript(DROPZONE_DIR.'/javascript/file_attachment_field.js');
        if($this->isCMS()) {
            Requirements::javascript(DROPZONE_DIR.'/javascript/file_attachment_field_backend.js');
        }
        Requirements::css(DROPZONE_DIR.'/css/file_attachment_field.css');

        if(!$this->getSetting('url')) {
            $this->settings['url'] = $this->Link('upload');
        }

        if(!$this->getSetting('maxFilesize')) {
            $this->settings['maxFilesize'] = static::get_filesize_from_ini();
        }
        // The user may not have opted into a multiple upload. If the form field
        // is attached to a record that has a multi relation, set that automatically.
        $this->settings['uploadMultiple'] = $this->IsMultiple();

        // Auto filter images if assigned to an Image relation
        if($class = $this->getFileClass()) {
            if(Injector::inst()->get($class) instanceof Image) {
                $this->imagesOnly();
            }
        }
    }

    /**
     * Saves the field into a record
     * @param  DataObjectInterface $record
     * @return FileAttachmentField
     */
    public function saveInto(DataObjectInterface $record) {
        $fieldname = $this->getName();
        if(!$fieldname) return $this;

        // Handle deletions. This is a bit of a hack. A workaround for having a single form field
        // post two params.
        $deletions = Controller::curr()->getRequest()->postVar('__deletion__'.$this->getName());

        if($deletions) {
            foreach($deletions as $id) {
                $this->deleteFileByID($id);
            }
        }

        if(($relation = $this->getRelation($record))) {
            $relation->setByIDList((array) $this->Value());
        } elseif($record->has_one($fieldname)) {
            $record->{"{$fieldname}ID"} = $this->Value() ?: 0;
        } elseif($record->hasField($fieldname)) {
			$record->$fieldname = is_array($this->Value()) ? implode(',', $this->Value()) : $this->Value();
		}

        if ($this->getTrackFiles()) {
            $fileIDs = (array)$this->Value();
            FileAttachmentFieldTrack::untrack($fileIDs);
        }

        return $this;
    }

    /**
     * Set the form method, e.g. PUT
     * @param string $method
     * @return  FileAttachmentField
     */
    public function setMethod($method) {
        $this->settings['method'] = $method;

        return $this;
    }

    /**
     * Return whether files are tracked or not.
     * @return boolean
     */
    public function getTrackFiles() {
        if (isset($this->settings['trackFiles']) && $this->settings['trackFiles'] !== null) {
            return $this->settings['trackFiles'];
        }
        return $this->config()->track_files;
    }

    /**
     * Enable/disable file tracking on uploads
     * @param boolean $bool
     * @return  FileAttachmentField
     */
    public function setTrackFiles($bool) {
        $this->settings['trackFiles'] = $bool;
        return $this;
    }

    /**
     * Sets number of allowed parallel uploads
     * @param int $num
     * @return  FileAttachmentField
     */
    public function setParallelUploads($num) {
        $this->settings['parallelUploads'] = $num;

        return $this;
    }

    /**
     * Allow multiple files
     * @param boolean $bool
     * @return  FileAttachmentField
     */
    public function setMultiple($bool) {
        $this->settings['uploadMultiple'] = $bool;

        return $this;
    }

    /**
     * Max filesize for uploads, in megabytes.
     * Defaults to upload_max_filesize
     * @param string $num
     * @return  FileAttachmentField
     */
    public function setMaxFilesize($num) {
        $this->settings['maxFilesize'] = $num;

        return $this;
    }

    /**
     * Maximum number of files allowed to be attached
     * @param int $num
     * @return $this
     */
    public function setMaxFiles($num){
        $this->settings['maxFiles'] = $num;

        return $this;
    }

    /**
     * Maximum number of files allowed to be attached
     * (Keeps API consistent with UploadField)
     * @param int $num
     * @return $this
     */
    public function setAllowedMaxFileNumber($num) {
        return $this->setMaxFiles($num);
    }

    /**
     * Sets the name of the upload parameter, e.g. "Files"
     * @param string $name
     * @return  FileAttachmentField
     */
    public function setParamName($name) {
        $this->settings['paramName'] = $name;

        return $this;
    }

    /**
     * Allow or disallow image thumbnails created client side
     * @param boolean $bool
     * @return  FileAttachmentField
     */
    public function setCreateImageThumbnails($bool) {
        $this->settings['createImageThumbnails'] = $bool;

        return $this;
    }

    /**
     * Set the threshold at which to not create an image thumbnail
     * @param int $num
     * @return  FileAttachmentField
     */
    public function setMaxThumbnailFilesize($num) {
        $this->settings['thumbnailFilesize'] = $num;

        return $this;
    }

    /**
     * Add an array of IDs
     *
     * @return void
     */
    public function addValidFileIDs(array $ids) {
        $validIDs = Session::get('FileAttachmentField.validFileIDs');
        if (!$validIDs) {
            $validIDs = array();
        }
        foreach ($ids as $id) {
            $validIDs[$id] = $id;
        }
        Session::set('FileAttachmentField.validFileIDs', $validIDs);
    }

    /**
     * Get an associative array of File IDs uploaded through this field
     * during this session.
     *
     * @return array
     */
    public function getValidFileIDs() {
        $validIDs = Session::get('FileAttachmentField.validFileIDs');
        if ($validIDs && is_array($validIDs)) {
            return $validIDs;
        }
        return array();
    }

    /**
     * Check that the user is submitting the file IDs that they uploaded.
     *
     * @return boolean
     */
    public function validate($validator) {
        $result = true;

        // Detect if files have been removed between AJAX uploads and form submission
        $value = $this->dataValue();
        if ($value) {
            $ids = (array)$value;
            $fileCount = (int)File::get()->filter(array('ID' => $ids))->count();
            if (count($ids) !== $fileCount) {
                $validator->validationError(
                    $this->name,
                    _t(
                        'FileAttachmentField.MISSINGFILE',
                        'Files sent with form have since been removed from the server.'
                    ),
                    "validation"
                );
                $result = false;
            }
        }

        if ($this->hasInvalidFileID) {
            // If detected invalid file during 'Form::loadDataFrom'
            // (Below validation isn't triggered as setValue() removes the invalid ID
            //  to prevent the CMS from loading something it shouldn't, also stops the
            //  validator from realizing there's an invalid ID.)
            $validator->validationError(
                $this->name,
                _t(
                    'FileAttachmentField.VALIDATION',
                    'Invalid file ID sent.'
                ),
                "validation"
            );
            $result = false;
        } else if ($value && is_array($value)) {
            // Prevent a malicious user from inspecting element and changing
            // one of the <input type="hidden"> fields to use an invalid File ID.
            $validIDs = $this->getValidFileIDs();
            foreach ($value as $id) {
                if (!isset($validIDs[$id])) {
                    if ($validator) {
                        $validator->validationError(
                            $this->name,
                            _t(
                                'FileAttachmentField.VALIDATION',
                                'Invalid file ID sent.',
                                array('id' => $id)
                            ),
                            "validation"
                        );
                    }
                    $result = false;
                }
            }
        }

        return $result;
    }

    /**
     * @param int|array $val
     * @param array|DataObject $data
     * @return $this
     */
    public function setValue($val, $data = array()) {
        if (!$val && $data && $data instanceof DataObject && $data->exists()) {
            // NOTE: This stops validation errors from occuring when editing
            //       an already saved DataObject.
            $fieldName = $this->getName();
            $ids = array();
            if ($data->hasOneComponent($fieldName)) {
                $id = $data->{$fieldName.'ID'};
                if ($id) {
                   $ids[] = $id; 
                }
            } else if ($data->hasManyComponent($fieldName) || $data->manyManyComponent($fieldName)) {
                $files = $data->{$fieldName}();
                if ($files) {
                    foreach ($files as $file) {
                        if (!$file->exists()) {
                            continue;
                        }
                        $ids[] = $file->ID; 
                    }
                }
            }
            if ($ids) {
                $this->addValidFileIDs($ids);
            }
        }
        if ($data && is_array($data) && isset($data[$this->getName()])) {
            // Prevent Form::loadDataFrom() from loading invalid File IDs
            // that may have been passed.
            $isInvalid = false;
            $validIDs = $this->getValidFileIDs();
            // NOTE(Jake): If the $data[$name] is an array, its coming from 'loadDataFrom'
            //             If its a single value, its just re-populating the ID on DB data most likely.
            if (is_array($data[$this->getName()])) {
                $ids = &$data[$this->getName()];
                foreach ($ids as $i => $id) {
                    if ($validIDs && !isset($validIDs[$id])) {
                        unset($ids[$i]);
                        $isInvalid = true;
                    }
                }
                if ($isInvalid) {
                    $ids = array_values($ids);
                    $val = $ids;
                    $this->hasInvalidFileID = true;
                }
                unset($ids); // stop $ids variable from modifying to $data array.
            }
        }
        return parent::setValue($val, $data);
    }

    /**
     * The thumbnail width
     * @param int $num
     * @return  FileAttachmentField
     */
    public function setThumbnailWidth($num) {
        $this->settings['thumbnailWidth'] = $num;

        return $this;
    }

    /**
     * The thumbnail height
     * @param int $num
     * @return  FileAttachmentField
     */
    public function setThumbnailHeight($num) {
        $this->settings['thumbnailHeight'] = $num;

        return $this;
    }

    /**
     * The layout of the uploader, either "grid" or "list"
     * @param string $view
     * @return  FileAttachmentField
     */
    public function setView($view) {
        if(!in_array($view, array ('grid','list'))) {
            throw new Exception("FileAttachmentField::setView - View must be one of 'grid' or 'list'");
        }

        $this->view = $view;

        return $this;
    }

    /**
     * Gets the current view
     * @return string
     */
    public function getView() {
        return $this->view;
    }

    /**
     * Set the selector for the clickable element. Use a boolean for the
     * entire dropzone.
     * @param string|bool $val
     * @return  FileAttachmentField
     */
    public function setClickable($val) {
        $this->settings['clickable'] = $val;

        return $this;
    }

    /**
     * A list of accepted file extensions
     * @param array $files
     * @return  FileAttachmentField
     */
    public function setAcceptedFiles($files = array ()) {
        if(is_array($files)) {
            $files = implode(',', $files);
        }
        $this->settings['acceptedFiles'] = str_replace(' ', '', $files);

        return $this;
    }

    /**
     * A helper method to only allow images files
     * @return FileAttachmentField
     */
    public function imagesOnly() {
        $this->setAcceptedFiles(array('.png','.gif','.jpeg','.jpg'));

        return $this;
    }

    /**
     * Sets the allowed mime types
     * @param array $types
     * @return  FileAttachmentField
     */
    public function setAcceptedMimeTypes($types = array ()) {
        if(is_array($types)) {
            $types = implode(',', $types);
        }
        $this->settings['acceptedMimeTypes'] = $types;

        return $this;
    }

    /**
     * Set auto-processing. If true, uploads happen on addition to the queue
     * @param boolean $bool
     * @return  FileAttachmentField
     */
    public function setAutoProcessQueue($bool) {
        $this->settings['autoProcessQueue'] = $bool;

        return $this;
    }

    /**
     * Set the selector for the container element that holds all of the
     * uploaded files
     * @param string $val
     * @return  FileAttachmentField
     */
    public function setPreviewsContainer($val) {
        $this->settings['previewsContainer'] = $val;

        return $this;
    }

    /**
     * Sets the max resolution for images, in pixels
     * @param int $pixels
     */
    public function setMaxResolution($pixels) {
    	$this->settings['maxResolution'] = $pixels;

    	return $this;
    }

    /**
     * Sets the min resolution for images, in pixels
     * @param int $pixels
     */
    public function setMinResolution($pixels) {
    	$this->settings['minResolution'] = $pixels;
    	return $this;
    }

    /**
     * Sets selector for the preview template
     * @param string $template
     * @return  FileAttachmentField
     */
    public function setPreviewTemplate($template) {
        $this->previewTemplate = $template;

        return $this;
    }

    /**
     * Adds an arbitrary key/val params to send to the server with the upload
     * @param string $key
     * @param mixed $val
     * @return  FileAttachmentField
     */
    public function addParam($key, $val) {
        $this->params[$key] = $val;

        return $this;
    }

    /**
     * Sets permissions for this uploader: "detach", "upload", "delete", "attach"
     * Permissions can be boolean or Callable
     * @param array $perms
     * @return  FileAttachmentField
     */
    public function setPermissions($perms) {
        foreach($perms as $perm => $val) {
            if(!isset($this->permissions[$perm])) {
                throw new Exception("FileAttachmentField::setPermissions - Permission $perm is not allowed");
            }
            $this->permissions[$perm] = $val;
        }

        return $this;
    }

    /**
     * Sets a specific permission for this uploader: "detach", "upload", "delete", "attach"
     * Permissions can be boolean or Callable
     *
     * @param string $perm
     * @param boolean|Callable $val
     * @return  FileAttachmentField
     */
    public function setPermission($perm, $val) {
        return $this->setPermissions(array(
            $perm => $val
        ));
    }

	/**
	 * @param String
	 */
	public function setDisplayFolderName($name) {
		$this->displayFolderName = $name;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getDisplayFolderName() {
		return $this->displayFolderName;
	}

    /**
     * Returns true if the uploader is being used in CMS context
     * @return boolean
     */
    public function isCMS() {
        return Controller::curr() instanceof LeftAndMain;
    }

    /**
     * Action to handle upload of a single file
     *
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     * @return SS_HTTPResponse
     */
    public function upload(SS_HTTPRequest $request) {
        if($this->isDisabled() || $this->isReadonly() || !$this->CanUpload()) {
            return $this->httpError(403);
        }

        $form = $this->getForm();
        if($form) {
            $token = $form->getSecurityToken();
            if(!$token->checkRequest($request)) return $this->httpError(400);
        }

        $name = $this->getSetting('paramName');
        $files = $_FILES[$name];
        $tmpFiles = array();

        // Sort the files out into a list of arrays containing each property
        if(!empty($files['tmp_name']) && is_array($files['tmp_name'])) {
            for($i = 0; $i < count($files['tmp_name']); $i++) {
                if(empty($files['tmp_name'][$i])) continue;
                $tmpFile = array();
                foreach(array('name', 'type', 'tmp_name', 'error', 'size') as $field) {
                    $tmpFile[$field] = $files[$field][$i];
                }
                $tmpFiles[] = $tmpFile;
            }
        }
        elseif(!empty($files['tmp_name'])) {
            $tmpFiles[] = $files;
        }

        $ids = array ();
        foreach($tmpFiles as $tmpFile) {
            if($tmpFile['error']) {
                return $this->httpError(400, $tmpFile['error']);
            }
            if($relationClass = $this->getFileClass($tmpFile['name'])) {
                $fileObject = Object::create($relationClass);
            }

            try {
                $this->upload->loadIntoFile($tmpFile, $fileObject, $this->getFolderName());
                $ids[] = $fileObject->ID;
            } catch (Exception $e) {
                return $this->httpError(400, $e->getMessage());
            }

            if ($this->upload->isError()) {
                return $this->httpError(400, implode(' ' . PHP_EOL, $this->upload->getErrors()));
            }

            if ($this->getTrackFiles()) {
                $controller = Controller::has_curr() ? Controller::curr() : null;
                $formClass = ($form) ? get_class($form) : '';

                $trackFile = FileAttachmentFieldTrack::create();
                if ($controller instanceof LeftAndMain) {
                    // If in CMS (store DataObject or Page)
                    $formController = $form->getController();
                    $trackFile->ControllerClass = $formController->class;
                    if (!$formController instanceof LeftAndMain) {
                        $trackFile->setRecord($formController->getRecord());
                    }
                } else if ($formClass !== 'Form') {
                    $trackFile->ControllerClass = $formClass;
                } else {
                    // If using generic 'Form' instance, get controller
                    $trackFile->ControllerClass = $controller->class;
                }
                $trackFile->FileID = $fileObject->ID;
                $trackFile->write();
            }
        }

        $this->addValidFileIDs($ids);
        return new SS_HTTPResponse(implode(',', $ids), 200);
    }


    /**
     * @param SS_HTTPRequest $request
     * @return UploadField_ItemHandler
     */
    public function handleSelect(SS_HTTPRequest $request) {
        if($this->isDisabled() || $this->isReadonly() || !$this->CanAttach()) {
            return $this->httpError(403);
        }

        return FileAttachmentField_SelectHandler::create($this, $this->getFolderName());
    }


    /**
     * Deletes a file. Ensures user has permissions and the file is part
     * of the current record, so as not to allow arbitrary deletion of files
     *
     * @param  int $id
     * @return boolean
     */
    protected function deleteFileByID($id) {
        if($this->CanDelete() && $record = $this->getRecord()) {
            if($relation = $this->getRelation()) {
                $file = $relation->byID($id);
            }
            else if($record->has_one($this->getName())) {
                $file = $record->{$this->getName()}();
            }

            if($file && $file->canDelete()) {
                $file->delete();

                return true;
            }
        }

        return false;
    }

    /**
     * A template accessor that determines if the uploader is in "multiple" mode
     *
     * @return  boolean
     */
    public function IsMultiple() {
        if($this->getSetting('uploadMultiple')) {
            return true;
        }

        if($record = $this->getRecord()) {
            return ($record->many_many($this->getName()) || $record->has_many($this->getName()));
        }

        return false;
    }

    /**
     * The name of the input, e.g. the "has_one" or "many_many" relation name
     *
     * @return  string
     */
    public function InputName() {
        return $this->IsMultiple() ? $this->getName()."[]" : $this->getName();
    }

    /**
     * Gets a list of all the files that are attached to the record
     *
     * @return  SS_List
     */
    public function AttachedFiles() {
        if($record = $this->getRecord()) {
            if($record->hasMethod($this->getName())) {
                $result = $record->{$this->getName()}();
                if($result instanceof SS_List) {
                    return $result;
                }
                else if($result->exists()) {
                    return ArrayList::create(array($result));
                }
            }
        }

		if ($ids = $this->dataValue()) {
			if($ids instanceof ManyManyList) {
				$ids = array_keys($ids->map()->toArray());
			}

			if (!is_array($ids)) {
				$ids = explode(',', $ids);
			}

			$attachments = ArrayList::create();
			foreach ($ids as $id) {
				$file = File::get()->byID((int) $id);
				if ($file && $file->canView()) {
					$attachments->push($file);
				}
			}
			return $attachments;
		}

        return false;
    }

    /**
     * Gets the directory that contains all the file icons organised into sizes
     *
     * @return  string
     */
    public function RootThumbnailsDir() {
        return $this->getSetting('thumbnailsDir') ?: DROPZONE_DIR.'/images/file-icons';
    }

    /**
     * Gets the directory to the file icons for the current thumbnail size
     *
     * @return  string
     */
    public function ThumbnailsDir() {
        return $this->RootThumbnailsDir().'/'.$this->TemplateThumbnailSize()."px";
    }


    public function CSSSize() {
        $w = $this->getSelectedThumbnailWidth();
        if($w < 150) return "small";
        if($w < 250) return "medium";

        return "large";
    }


    /**
     * The directory that the module is installed to. A template accessor
     *
     * @return  string
     */
    public function DropzoneDir() {
        return DROPZONE_DIR;
    }

    /**
     * Gets the value
     *
     * @return  string|array
     */
    public function Value() {
        return $this->dataValue();
    }

    /**
     * Returns true if the "upload" permission returns true
     *
     * @return  boolean
     */
    public function CanUpload() {
        return $this->checkPerm('upload');
    }

    /**
     * Returns true if the "delete" permission returns true
     *
     * @return  boolean
     */
    public function CanDelete() {
        return $this->checkPerm('delete');
    }

    /**
     * Returns true if the "detach" permission returns true
     *
     * @return  boolean
     */
    public function CanDetach() {
        return $this->checkPerm('detach');
    }

    /**
     * Returns true if the "attach" permission returns true
     *
     * @return  boolean
     */
    public function CanAttach() {
        return $this->checkPerm('attach');
    }

    /**
     * Renders the preview template, optionally for a given file
     * @param int $fileID
     */
    public function PreviewTemplate($fileID = null) {
        return $this->renderWith($this->previewTemplate);

    }

    /**
     * Gets the closest thumbnail size for the template, given the list of
     * icon_sizes (e.g. 32px, 64px, 128px)
     *
     * @return  int
     */
    public function TemplateThumbnailSize() {
        $w = $this->getSelectedThumbnailWidth();

        foreach($this->config()->icon_sizes as $size) {
            if($w <= $size) return $size;
        }
    }

    /**
     * Returns true if the uploader auto-processes
     *
     * @return  boolean
     */
    public function AutoProcess() {
        $result = (bool) $this->getSetting('autoProcessQueue');

        return $result;
    }

    /**
     * Checks for a given permission. If it is a closure, invoke the method
     * @param  string $perm
     * @return boolean
     */
    protected function checkPerm($perm) {
        if(!isset($this->permissions[$perm])) return false;

        if(is_callable($this->permissions[$perm])) {
            return $this->permissions[$perm]();
        }

        return $this->permissions[$perm];
    }

    /**
     * Gets the classname for the file, e.g. from the declared
     * file relation on the record.
     *
     * If given a filename, look at the extension and upgrade it
     * to an Image if necessary.
     *
     * @param  string $filename
     * @return string
     */
    public function getFileClass($filename = null) {
        $name = $this->getName();
        $record = $this->getRecord();

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $defaultClass = File::get_class_for_file_extension($ext);

        if(empty($name) || empty($record)) {
            return $defaultClass;
        }

        if($record) {
    	    $class = $record->getRelationClass($name);
        	if(!$class) $class = "File";
    	}

        if($filename) {
            if($defaultClass == "Image" &&
               $this->config()->upgrade_images &&
               !Injector::inst()->get($class) instanceof Image
            ) {
                $class = "Image";
            }
        }

        return $class;
    }

    /**
     * Get the record that this form field is editing
     * @return DataObject
     */
    public function getRecord() {
        if (!$this->record && $this->form) {
            if (($record = $this->form->getRecord()) && ($record instanceof DataObject)) {
                $this->record = $record;
            }
            elseif (($controller = $this->form->Controller())
                && $controller->hasMethod('data')
                && ($record = $controller->data())
                && ($record instanceof DataObject)
            ) {
                $this->record = $record;
            }
        }

        return $this->record;
    }

    /**
     * Gets the name of the relation, if attached to a record
     * @return string
     */
    protected function getRelation($record = null) {
        if(!$record) $record = $this->getRecord();

        if($record) {
            $fieldname = $this->getName();
            $relation = $record->hasMethod($fieldname) ? $record->$fieldname() : null;

            return ($relation && ($relation instanceof RelationList || $relation instanceof UnsavedRelationList)) ? $relation : false;
        }

        return false;
    }

    /**
     * Gets a given setting. Falls back on Config defaults
     *
     * Note: config settings are in underscore_case
     *
     * @param  string $setting
     * @return mixed
     */
    protected function getSetting($setting) {
        if(isset($this->settings[$setting])) {
            return $this->settings[$setting];
        }

        $config = Config::inst()->get(__CLASS__, "defaults");
        $configName = static::underscorise($setting);

        return isset($config[$configName]) ? $config[$configName] : null;
    }

    /**
     * Gets the default settings in the actual Javascript object so that
     * the config JSON doesn't get polluted with default settings
     *
     * @return array
     */
    protected function getDefaults() {
        $file_path = BASE_PATH.'/'.DROPZONE_DIR.'/'.$this->config()->default_config_path;
        if(!file_exists($file_path)) {
            throw new Exception("FileAttachmentField::getDefaults() - There is no config json file at $file_path");
        }

        return Convert::json2array(file_get_contents($file_path));
    }

    /**
     * Gets the thumbnail width given the current view type
     * @return int
     */
    public function getSelectedThumbnailWidth() {
        if($w = $this->getSetting('thumbnailWidth')) {
            return $w;
        }

        $setting = $this->view == "grid" ? 'grid_thumbnail_width' : 'list_thumbnail_width';

        return $this->config()->$setting;
    }

    /**
     * Gets the thumbnail height given the current view type
     * @return int
     */
    public function getSelectedThumbnailHeight() {
        if($h = $this->getSetting('thumbnailHeight')) {
            return $h;
        }

        $setting = $this->view == "grid" ? 'grid_thumbnail_height' : 'list_thumbnail_height';

        return $this->config()->$setting;
    }

    /**
     * Creates a JSON representation of the settings. Augments the list with various
     * parameters calculated at run time.
     *
     * @return string
     */
    public function getConfigJSON() {
        $data = $this->settings;
        $defaults = $this->getDefaults();
        foreach($this->config()->defaults as $setting => $value) {
            $js_name = static::camelise($setting);

            // If the setting has been set on the instance, use that value
            if(isset($data[$js_name])) {
                continue;
            }

            // Only include the setting in the JSON if it differs from the core default value
            if(!isset($defaults[$js_name]) || ($defaults[$js_name] !== $value)) {
                $data[$js_name] = $value;
            }
        }

        $data['params'] = ($this->params) ? $this->params : null;
        $data['thumbnailsDir'] = $this->ThumbnailsDir();
        $data['thumbnailWidth'] = $this->getSelectedThumbnailWidth();
        $data['thumbnailHeight'] = $this->getSelectedThumbnailHeight();

        if(!$this->IsMultiple()) {
            $data['maxFiles'] = 1;
        }

        if($this->isCMS()) {
            $data['urlSelectDialog'] = $this->Link('select');
            if($this->getFolderName()) {
            	$data['folderID'] = Folder::find_or_make($this->getFolderName())->ID;
            }
        }

        return Convert::array2json($data);
    }
}

class FileAttachmentField_SelectHandler extends UploadField_SelectHandler {

    private static $allowed_actions = array (
        'filesbyid',
    );

    /**
     * @param $folderID The ID of the folder to display.
     * @return FormField
     */
    protected function getListField($folderID) {
        // Generate the folder selection field.
        $folderField = new TreeDropdownField('ParentID', _t('HtmlEditorField.FOLDER', 'Folder'), 'Folder');
        $folderField->setValue($folderID);

        // Generate the file list field.
        $config = GridFieldConfig::create();
        $config->addComponent(new GridFieldSortableHeader());
        $config->addComponent(new GridFieldFilterHeader());
        $config->addComponent($columns = new GridFieldDataColumns());
        $columns->setDisplayFields(array(
            'StripThumbnail' => '',
            'Name' => 'Name',
            'Title' => 'Title'
        ));
        $config->addComponent(new GridFieldPaginator(8));

        // If relation is to be autoset, we need to make sure we only list compatible objects.
        $baseClass = $this->parent->getFileClass();

        // Create the data source for the list of files within the current directory.
        $files = DataList::create($baseClass)->filter('ParentID', $folderID);

        $fileField = new GridField('Files', false, $files, $config);
        $fileField->setAttribute('data-selectable', true);
        if($this->parent->IsMultiple()) {
            $fileField->setAttribute('data-multiselect', true);
        }

        $selectComposite = new CompositeField(
            $folderField,
            $fileField
        );

        return $selectComposite;
    }


    public function filesbyid(SS_HTTPRequest $r) {
        $ids = $r->getVar('ids');
        $files = File::get()->byIDs(explode(',',$ids));

        $validIDs = array();
        $json = array ();
        foreach($files as $file) {
            $template = new SSViewer('FileAttachmentField_attachments');
            $html = $template->process(ArrayData::create(array(
                'File' => $file,
                'Scope' => $this->parent
            )));

            $validIDs[$file->ID] = $file->ID;
            $json[] = array (
                'id' => $file->ID,
                'html' => $html->forTemplate()
            );
        }

        $this->parent->addValidFileIDs($validIDs);
        return Convert::array2json($json);
    }

}
