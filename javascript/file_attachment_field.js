(function () {


/**
 * A wrapper for the Dropzone object that handles UI specifics for
 * its implementation in this module.
 * 
 * @param DOMElement node    The DOM element that is the dropzone
 * @param Object backend The Dropzone class (for DI)
 */
var UploadInterface = function (node, backend) {
    var template = node.querySelector('template');
    this.settings = JSON.parse(node.getAttribute('data-config'));
    this.node = node;
    this.droppedFiles = [];
    if (!this.settings.params) {
        this.settings.params = {};
    }

    if (!this.settings.params.SecurityID) {
        var formNode = this.node;
        do {
            formNode = formNode.parentNode;
        } while (formNode && formNode.tagName !== 'FORM' && formNode.nodeType !== 11);
        var securityIDNode = formNode.querySelector("input[name=\"SecurityID\"]");
        if (securityIDNode) {
            this.settings.params.SecurityID = securityIDNode.value;
        }
    }
    
    if(template) {
        this.settings.previewTemplate = template.innerHTML;
    }

    if(this.settings.clickable && typeof this.settings.clickable == 'string') {
        this.settings.clickable = '#'+this.node.id + ' ' + this.settings.clickable;
    }

    this.settings.previewsContainer = this.node.querySelector('[data-container]');
    this.settings.fallback = UploadInterface.prototype.fallback.bind(this);
    this.settings.accept = UploadInterface.prototype.accept.bind(this);
    
    if (document.documentElement.classList && this.node.classList.contains('uploadable')) {
        this.backend = new backend(this.node, this.settings);
    } else if (this.node.className.indexOf('uploadable') !== -1) {
        this.backend = new backend(this.node, this.settings);
    }

    this.initialize();

};


UploadInterface.prototype = {

    /**
     * Sets up the UI with all the event handlers     
     */
    initialize: function () {
        var _this = this;
        if(this.backend) {
            this.backend
                .on('addedfile', function (file) {
                    if(!_this.settings.uploadMultiple) {
                        _this.removeAttachedFiles();
                    }
                    _this.queueFile(file);
                })

                .on('removedfile', function (file) { 
                    if(droppedFile = _this.getFileByID(file.serverID)) {
                        droppedFile.destroy();                        
                    }         
                })
                
                .on('maxfilesexceeded', function(file) {
                    if(!this.options.uploadMultiple) {                
                        this.removeAllFiles();                
                        this.addFile(file);
                    }
                })

                .on('thumbnail', function (file) {
                    var file = _this.getFileByID(file.serverID);
                    if(file) {                
                        file.setDimensions();
                    }
                })

                .on('error', function (file, msg) {
                    this.getFileByID(file.serverID).showError()
                }.bind(this))
                        
                .on('success', function (file, response) {                
                    _this.persistFile(file, response);                
                })
                
                .on('successmultiple', function (files, response) {                
                    var ids = response.split(',');
                    for(var i = 0; i < files.length; i++) {
                        if(!files[i].uploaded) {                        
                            _this.persistFile(files[i], ids[i]);                        
                        }
                    }
                }.bind(this));
        }

        
        q('[data-attachments] li', this.node).forEach(function(li) {
            var fileID = li.getAttribute('data-id');
            _this.droppedFiles.push(new DroppedFile(_this, {serverID: fileID, uploaded: true}));

            _this.bindEvents(li, fileID);
        });

        q('[data-auto-process]', this.node).forEach(function (btn) {     
            btn.addEventListener('click', function (e) {            
                e.preventDefault();
                var interval;
                var process = function () {
                    if(_this.backend.getQueuedFiles().length) {
                        _this.backend.processQueue();                        
                    }
                    else {
                        clearInterval(interval); 
                    }
                };
                
                process();
                interval = setInterval(process, 500);
            });
        });
  
    },

    /**
     * Looks through the droppedFiles array for a specific file
     * @param  {string|int} id
     * @return {DroppedFile}
     */
    getFileByID: function (id) {
        var result = false;
        this.droppedFiles.some(function(file) {        
            if(file.getIdentifier() == id) {
                result = file;
                return true;
            }
        });

        return result;
    },

    /**
     * Saves a file as having been uploaded
     * @param  {File} file     The core File object
     * @param  {string} response The response from the server     
     */
    persistFile: function (file, response) {        
        if(response.indexOf(',') !== -1) return;

        var droppedFile = this.getFileByID(file.serverID);
        if(droppedFile) {            
            file.serverID = response;
            droppedFile.persist();
        }        
    },

    /**
     * Queues a file up in the droppedFiles array
     * @param  {File} file The core File object     
     */
    queueFile: function (file) {
        var droppedFile = new DroppedFile(this, file);
        this.droppedFiles.push(droppedFile);
        
        droppedFile.queue();
    },

    /**
     * Removes an uploaded file from the droppedFiles array
     * @param  {droppedFile} droppedFile      
     */
    removeDroppedFile: function (droppedFile) {
        for(var i in this.droppedFiles) {
            if(this.droppedFiles[i].getIdentifier() === droppedFile.getIdentifier()) {
                delete this.droppedFiles[i];
            }
        }
    },

    /**
     * Removes all files that are currently uploaded
     */
    removeAttachedFiles: function () {    
        q('[data-attachments] li', this.node).forEach(function(n) {     
            if(n.getAttribute('data-id')) {                
                n.parentNode.removeChild(n);
            }
        });
    },

    /**
     * The fallback method invoked when HTML5 uploads are not available.
     */
    fallback: function () {
        var div = this.node.parentNode.querySelector('.unsupported');
        this.node.style.display = 'none';
        div.style.display = 'block';
    },

    /**
     * Custom validation of dropped files. Check max resolution, etc.
     * @param  {File}   file 
     * @param  {Function} done      
     */
	accept: function (file, done) {
    	if((this.settings.maxResolution || this.settings.minResolution) && file.type.match(/image.*/)) {
		this.checkImageResolution(file, this.settings.maxResolution, this.settings.minResolution, function (result, errorType, width, height) {
			var msg = null;
			if(!result) {
				if(errorType == "big"){
					msg = 'Resolution is too high. Please resize to ' + width + 'x' + height + ' or smaller';
				}else{
					msg = 'Resolution is too small. Please resize to ' + width + 'x' + height + ' or bigger';
				}
			}
			try {				
				done(msg);
			}
			// Because this check is asynchronous, the file has already been queued at this point
			// and Dropzone throws an error for queuing a rejected file. Just ignore it.
			catch (e) {}
		});
	}
	else {
		return done();
	}
    },

    /**
     * Binds events to an LI tag representing an attached file
     * @param  {DOMElement} li 
     * @param  {Int} fileID     
     */
    bindEvents: function (li, fileID) {
        var _this = this;

        q('[data-delete-revert]', li).forEach(function (a) {
            a.addEventListener('click', function(e) {
                _this.getFileByID(fileID).revertDeletion();
            });
        });

        q('[data-delete]', li).forEach(function (a) { 
            a.addEventListener('click', function (e) {                
                e.preventDefault();                
                _this.getFileByID(fileID).markForDeletion();
            });
        });

        q('[data-detach]', li).forEach(function (a) {             
            a.addEventListener('click', function (e) {              
                e.preventDefault();                
                _this.getFileByID(fileID).markForDetachment();
            });
        })
    },

    /**
     * Reset the uploader. Remove all files.     
     */
    clear: function (force) {
        if(this.backend) {
            this.backend.removeAllFiles(force);
        }
        this.droppedFiles.forEach(function(file) {
            file.destroy();
        }.bind(this));
    },

    /**
     * A utility method for checking the resolution of a dropped file.
     * @param  {File}   file      
     * @param  {int}   maxPixels The maximum resolution, in pixels
     * @param  {int}   minPixels The minimum resolution, in pixels
     * @param  {Function} callback       
     */
    checkImageResolution: function (file, maxPixels, minPixels, callback) {
		var reader = new FileReader(),
			image  = new Image();			

		reader.readAsDataURL(file);  

		reader.onload = function(file) {
			image.src    = file.target.result;
			image.onload = function() {
				var imageW = this.width,
					imageH = this.height,
					pixels = imageW * imageH;					

					if(pixels > maxPixels) {
						var ratio = imageH / imageW,
							maxWidth = Math.floor(Math.sqrt(maxPixels / ratio)),
							maxHeight = Math.round(maxWidth * ratio);
							callback(false, 'big', maxWidth, maxHeight);
					}
				        if (pixels < minPixels) {
						var ratio = imageH / imageW,
							minWidth = Math.floor(Math.sqrt(minPixels / ratio)),
							minHeight = Math.round(minWidth * ratio);
							callback(false, 'small', minWidth, minHeight);

					}

					callback(true);
			};
		};
    }

};


/**
 * Defines a file that has been dropped (or selected) into the Dropzone UI.
 * It may or may not have been uploaded.
 * 
 * @param {Dropzone} uploader
 * @param {File} file
 */
var DroppedFile = function (uploader, file) {
    this.uploader = uploader;
    this.queued = false;    
    this.file = file;

    // If there is no ID yet, create one
    this.file.serverID = this.file.serverID || uuid();
};


DroppedFile.prototype = {

    /**
     * Gets the container for the hidden inputs representing the uploaded files
     * @return {Array}
     */
    getHolder: function () {
        return this.uploader.node.querySelector('.attached-file-inputs');
    },

    /**
     * Gets the DOM element that contains this file's UI (e.g. an LI tag)
     * @return {DOMElement}
     */
    getUI: function () {        
        return this.uploader.node.querySelector('[data-id="'+this.getIdentifier()+'"]');
    },

    /**
     * Removes the LI representing this file     
     */
    removeUI: function () {        
        this.getUI().parentNode.removeChild(this.getUI());
    },

    /**
     * Gets the unique ID for this file
     * @return {string|int}
     */
    getIdentifier: function () {
        return this.file.serverID;
    },

    /**
     * Get the name of this Dropzone, e.g. "MyFile"
     * @return {string}
     */
    getName: function () {
        return this.getHolder().getAttribute('data-input-name');
    },

    /**
     * Queue this file for uploading     
     */
    queue: function () {
        var file = this.file;
        var settings = this.uploader.settings;
        var imageDir = settings.thumbnailsDir;

        if(!file.type.match(/image.*/)) {
            file.previewElement.classList.add('has-preview');
            q('[data-dz-thumbnail]', file.previewElement).forEach(function(img) {
                var ext = file.name.split('.').pop();
                img.style.display = 'none';
                img.src = imageDir+"/"+ext+".png";
                
                img.onerror = function () {
                    this.src= imageDir+"/_blank.png";
                };
                img.onload = function () {                    
                    this.parentNode.style.backgroundImage = "url('"+this.src+"')";
                };          
            });

            this.setDimensions();   
        }
        else {
            this.uploader.backend.emit('thumbnail', file);
        }

        this.queued = true;
    },

    /**
     * Sets the dimensions of the image, whether a true thumbnail or
     * a stock file icon. Forces the image to fit into its container
     * cleanly, if for instance, thumb width and thumb height are not
     * equal.
     */
    setDimensions: function () {
        var settings = this.uploader.settings;    
        q('[data-dz-thumbnail]', this.file.previewElement).forEach(function(img) {
            if(settings.thumbnailWidth > settings.thumbnailHeight) {
                img.style.height = settings.thumbnailHeight+'px';
                img.removeAttribute('width');                
            }
            else {
                img.style.width = settings.thumbnailWidth+'px';
                img.removeAttribute('height');                
            }        
        });
    },

    /**
     * Saves the uploaded file as ready to submit in the form     
     */
    persist: function () {        
        this.createInput();
        this.file.previewElement.classList.add('success');
        this.file.uploaded = true;
    },

    /**
     * Gets the hidden input that represents this file
     * @return {DOMElement}
     */
    getInput: function () {
        return this.getHolder().querySelector('input.input-attached-file[value="'+this.getIdentifier()+'"]');
    },

    /**
     * Removes this file from the queue     
     */
    removeFromQueue: function () {
        var input = this.getInput();
        if(input) {
            input.parentNode.removeChild(input);
        }
    },

    /**
     * Removes from the queue and adds to the __deletion__ array     
     */
    markForDeletion: function () {
        if(this.getInput()) {
            var holder = this.uploader.node.querySelector('.attached-file-deletions');
            holder.appendChild(createElementFromString(
                '<input type="hidden" class="input-deleted-file" name="__deletion__'+this.getName()+'" value="'+this.getIdentifier()+'">'
            ));
            this.removeFromQueue();
            this.getUI().classList.add('removed','deleted');            
        }
    },

    /**
     * Removes from the queue, but also removes the UI, since this file is
     * already attached, i.e. was on the record when the page loaded.
     */
    markForDetachment: function () {
        this.removeFromQueue();
        if(this.file.uploaded) {
            this.getUI().classList.add('removed','detached');         
        }
        else {
            this.removeUI();
        }
    },

    /**
     * Creates a new hidden input for sending this upload to the server     
     */
    createInput: function () {
        this.getHolder().appendChild(createElementFromString(
            '<input type="hidden" class="input-attached-file" name="'+this.getName()+'" value="'+this.getIdentifier()+'">'
        ));
    },

    /**
     * Undoes a detach/delete state     
     */
    revertDeletion: function () {
	var holder = this.uploader.node.querySelector('.attached-file-deletions');    
	var del = holder.querySelector('input.input-deleted-file[value="'+this.getIdentifier()+'"]');
        if(del) {
            del.parentNode.removeChild(del);
        }
        if(!this.getInput()) {
            this.createInput();        
        }

        this.getUI().classList.remove('removed','detached','deleted');        
    },

    /**
     * Shows the error overlay     
     */
    showError: function  () {
        this.file.previewElement.querySelector('.error-overlay').style.display = 'block';
    },

    /**
     * Kills entire thing     
     */
    destroy: function () {
        this.removeFromQueue();
        this.uploader.removeDroppedFile(this);
        if(this.getUI()) {            
            this.removeUI();
        }
    }

};


/**
 * Helper function that creates a unique ID
 * @return {string}
 */
function uuid () {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
      return v.toString(16);
    });
};

/**
 * Helper method that injects HTML into the DOM
 * @param  {str} str The string of HTML
 * @return {DOMElement}
 */
function createElementFromString(str) {
    var div = document.createElement('DIV');
    div.innerHTML = str.trim();

    return div.firstChild;
}

/**
 * Helper method to run a querySelector and transform the result
 * into an array
 * @param  {string} selector 
 * @param  {string} context 
 * @return {Array}
 */
function q(selector, context) {
    var node = context || document;

    return [].slice.call(node.querySelectorAll(selector));
}

// Expose UploadInterface as a browser global
window.UploadInterface = UploadInterface;

// If entwine is available, i.e. CMS, use it.
if(typeof jQuery === 'function' && typeof jQuery.entwine === 'function') {
    jQuery('.dropzone-holder').entwine({
        onmatch: function () {
            var upload = new UploadInterface(this[0], Dropzone);
            if(this.hasClass('backend')) {
                this.data('dropzoneInterface', upload);
                this.data('dropzoneFile', DroppedFile);
            }
        }
    });
}
// If not, use a standard onLoad.
else {
    document.addEventListener('DOMContentLoaded', function(){   
        q('.dropzone-holder').forEach(function(node) {
            var upload = new UploadInterface(node, Dropzone);
            // If jQuery exists, use its data() method
            if(typeof jQuery === 'function') {
                jQuery(node).data('dropzoneInterface', upload);
            }
            // Otherwise, inject a browser global
            else {
                if(!window.dropzones) window.dropzones = {};
                window.dropzones[node.id] = upload;
            }
        });
    });
}
})();
