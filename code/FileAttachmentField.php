<?php


class FileAttachmentField extends FileField
{

    private static $allowed_actions = array (
        'upload'
    );


    protected $settings = array ();


    protected $params = array ();

    
    protected $canUpload = true;


    protected $record = null;


    public function __construct ($name, $title = null, $value = null, $form = null) {
        $bytes = min(array(
            File::ini2bytes(ini_get('post_max_size') ?: '8M'),
            File::ini2bytes(ini_get('upload_max_filesize') ?: '2M')
        )); 

        $this->settings['maxFilesize'] = floor($bytes/(1024*1024));

        return parent::__construct($name, $title, $value, $form);
    }
    

    protected function getSetting($setting) {
        if(isset($this->settings[$setting])) {
            return $this->settings[$setting];
        }

        $config = Config::inst()->get(__CLASS__, "defaults");

        return isset($config[$setting]) ? $config[$setting] : null;
    }


    protected function getDefaults() {
        $file_path = BASE_PATH.'/'.DROPZONE_DIR.'/'.$this->config()->default_config_path;
        if(!file_exists($file_path)) {
            throw new Exception("FileAttachmentField::getDefaults() - There is no config json file at $file_path");
        }

        return Convert::json2array(file_get_contents($file_path));        
    }


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
            if(!isset($defaults[$js_name]) || ($defaults[$js_name] != $value)) {
                $data[$js_name] = $value;
            }
        }

        $data['params'] = $this->params;

        return Convert::array2json($data);
    }


    /**
     * Action to handle upload of a single file
     * 
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     * @return SS_HTTPResponse
     */
    public function upload(SS_HTTPRequest $request) {
        if($this->isDisabled() || $this->isReadonly() || !$this->canUpload) {
            return $this->httpError(403);
        }

        $token = $this->getForm()->getSecurityToken();
        if(!$token->checkRequest($request)) return $this->httpError(400);
                
        $name = $this->getSetting('param_name');
        $files = $_FILES[$name];
        $tmpFiles = array();
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

        if($tmpFile['error']) {
            return $this->httpError(400, $tmpFile['error']);
        }
        
        if($relationClass = $this->getFileClass()) {          
            $fileObject = Object::create($relationClass);
        }

        try {
            $this->upload->loadIntoFile($tmpFile, $fileObject, $this->getFolderName());
        } catch (Exception $e) {
            return $this->httpError(400, $e->getMessage());            
        }

        if ($this->upload->isError()) {
            return $this->httpError(400, implode(' ' . PHP_EOL, $this->upload->getErrors()));
        }

        return new SS_HTTPResponse($fileObject->ID, 200);

    }


    protected function getFileClass() {        
        $name = $this->getName();
        $record = $this->getRecord();
        if(empty($name) || empty($record)) {
            return "File";
        }

        $class = $record->getRelationClass($name);
        return empty($class) ? "File" : $class;
    }


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



    public function FieldHolder($attributes = array ()) {                
        Requirements::javascript(DROPZONE_DIR.'/javascript/dropzone.js');
        Requirements::javascript(DROPZONE_DIR.'/javascript/file_attachment_field.js');
        Requirements::css(DROPZONE_DIR.'/css/file_attachment_field.css');

        if(!$this->getSetting('url')) {
            $this->settings['url'] = $this->Link('upload');
        }

        if($token = $this->getForm()->getSecurityToken()) {
            $this->addParam($token->getName(), $token->getSecurityID());
        }
        

        return $this->renderWith('FileAttachmentField');
    }


    public function setCanUpload($bool) {
        $this->canUpload = (bool) $bool;

        return $this;
    }


    public function setMethod ($method) {
        $this->settings['method'] = $method;

        return $this;
    }


    public function setParallelUploads ($num) {
        $this->settings['parallelUploads'] = $num;

        return $this;
    }


    public function setUploadMultiple ($bool) {
        $this->settings['uploadMultiple'] = $bool;

        return $this;
    }


    public function setMaxFilesize ($num) {
        $this->settings['maxFilesize'] = $num;

        return $this;
    }


    public function setParamName ($name) {
        $this->settings['paramName'] = $name;

        return $this;
    }


    public function setCreateImageThumbnails ($bool) {
        $this->settings['createImageThumbnails'] = $bool;

        return $this;
    }


    public function setMaxThumbnailFilesize ($num) {
        $this->settings['thumbnailFilesize'] = $num;

        return $this;
    }


    public function setThumbnailWidth ($num) {
        $this->settings['thumbnailWidth'] = $num;

        return $this;
    }


    public function setThumbnailHeight ($num) {
        $this->settings['thumbnailHeight'] = $num;

        return $this;
    }


    public function setClickable ($bool) {
        $this->settings['clickable'] = $bool;

        return $this;
    }


    public function setAcceptedFiles ($files = array ()) {
        if(is_array($files)) {
            $files = explode(',', $files);
        }
        $this->settings['acceptedFiles'] = $files;

        return $this;
    }


    public function setAcceptedMimeTypes ($types = array ()) {
        if(is_array($types)) {
            $types = explode(',', $types);
        }
        $this->settings['acceptedMimeTypes'] = $types;

        return $this;
    }


    public function setAutoProcessQueue ($bool) {
        $this->settings['autoProcessQueue'] = $bool;

        return $this;
    }


    public function setAutoQueue ($bool) {
        $this->settings['autoQueue'] = $bool;

        return $this;
    }


    public function setAddRemoveLinks ($bool) {
        $this->settings['addRemoveLinks'] = $bool;

        return $this;
    }


    public function setPreviewsContainer ($val) {
        $this->settings['previewsContainer'] = $val;

        return $this;
    }


    public function addParam($key, $val) {
        $this->params[$key] = $val;

        return $this;
    }


    public static function camelise($str) {
        return preg_replace_callback('/_([a-z])/', function ($c) {
                return strtoupper($c[1]);
        }, $str);
    }


}