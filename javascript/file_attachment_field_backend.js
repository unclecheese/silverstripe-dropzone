(function($) {
$('.dropzone-holder.backend').entwine({
	openSelectDialog: function() {
		// Create dialog and load iframe
		var uploadedFile, self = this, config = this.data('config'), dialogId = 'ss-uploadfield-dialog-' + this.attr('id'), dialog = jQuery('#' + dialogId);
		if(!dialog.length) dialog = jQuery('<div class="ss-uploadfield-dialog" id="' + dialogId + '" />');
		// If user selected 'Choose another file', we need the ID of the file to replace
		var iParams = config['urlSelectDialog'].indexOf('?'), i
		    iframeUrl = iParams > -1 ? config['urlSelectDialog'].substr(0, iParams) : config['urlSelectDialog'];
		    iframeParams = config['urlSelectDialog'].substr(iParams+1);
		var uploadedFileId = null;
		if (uploadedFile && uploadedFile.attr('data-fileid') > 0){
			uploadedFileId = uploadedFile.attr('data-fileid');
		}
		// Show dialog
		dialog.ssdialog({iframeUrl: iframeUrl + '?' + iframeParams, height: 550});

		// TODO Allow single-select
		dialog.find('iframe').bind('load', function(e) {
			var contents = $(this).contents(), gridField = contents.find('.ss-gridfield');
			// TODO Fix jQuery custom event bubbling across iframes on same domain
			// gridField.find('.ss-gridfield-items')).bind('selectablestop', function() {
			// });

			// Remove top margin (easier than including new selectors)
			contents.find('table.ss-gridfield').css('margin-top', 0);

			// Can't use live() in iframes...
			contents.find('input[name=action_doAttach]').unbind('click.openSelectDialog').bind('click.openSelectDialog', function() {				
				// TODO Fix entwine method calls across iframe/document boundaries
				var ids = $.map(gridField.find('.ss-gridfield-item.ui-selected'), function(el) {return $(el).data('id');});
				if(ids && ids.length) {
					$.ajax({
						url: iframeUrl+'/filesbyid?ids='+ids.join(',') + '&' + iframeParams,
						dataType: 'JSON',
						success: function (json) {
							json.forEach(function(item) {
								self.attachFile(item.id, item.html);
							});						
						}
					});
					
				}

				dialog.ssdialog('close');
				return false;
			});
		});
		dialog.ssdialog('open');
	},

	attachFile: function (id, html) {
		var uploader = this.data('dropzoneInterface');
		var DroppedFile = this.data('dropzoneFile');		
		var file = new DroppedFile(uploader, {serverID: id});
		var $li = $(html);
		
		if(!uploader.settings.uploadMultiple) {		
			uploader.clear();
		}

		uploader.droppedFiles.push(file);
		file.createInput();		
		$(uploader.settings.previewsContainer).append($li);
		uploader.bindEvents($li[0], id);
	}	
});

$('.dropzone-holder.backend a.dropzone-select-existing').entwine({
	onclick: function(e) {
		e.preventDefault();
		this.closest('.dropzone-holder').openSelectDialog();
	}
});

})(jQuery);
