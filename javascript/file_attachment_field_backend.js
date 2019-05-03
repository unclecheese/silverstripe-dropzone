(function($) {
$('.dropzone-holder.backend').entwine({

	IFrameUrl: null,

	IFrameParams: null,

	getConfig: function (opt) {
		var config = this.data('config');

		return opt ? config[opt] : config;
	},

	openKickAssets: function () {
		var self = this;
		var config = this.getConfig();
		var method = this.data('dropzoneInterface').settings.uploadMultiple ? 'requestFiles' : 'requestFile';
		var perms = {
			canDelete: false,
			canUpload: false,
			canCreateFolder: false,
			allowedExtensions: config.acceptedFiles,
			folderID: config.folderID || 0
		};

		window.KickAssets[method](perms, function (response) {
			if(!response) return;

			var files = (method === 'requestFile') ? [response] : response;
			var ids = files.map(function (f) {
				return f.id;
			});

			self.requestFileTemplates(ids);
		});
	},

	openSelectDialog: function() {
		// Create dialog and load iframe
		var config = this.getConfig();
		// If user selected 'Choose another file', we need the ID of the file to replace
		var iParams = config['urlSelectDialog'].indexOf('?'), i
		    iframeUrl = iParams > -1 ? config['urlSelectDialog'].substr(0, iParams) : config['urlSelectDialog'];
		    iframeParams = config['urlSelectDialog'].substr(iParams+1);

		this.setIFrameUrl(iframeUrl);
		this.setIFrameParams(iframeParams);

		if(window.KickAssets) {
			this.openKickAssets();
		}
		else {
			this.openAssetAdmin();
		}
	},

	openAssetAdmin: function (iframeUrl, iframeParams) {
		var self = this;
		var dialogId = 'ss-uploadfield-dialog-' + this.attr('id'),
						dialog = jQuery('#' + dialogId);

		if(!dialog.length) dialog = jQuery('<div class="ss-uploadfield-dialog" id="' + dialogId + '" />');

		// Show dialog
		dialog.ssdialog({iframeUrl: this.getIFrameUrl() + '?' + this.getIFrameParams(), height: 550});

		// TODO Allow single-select
		dialog.find('iframe').bind('load', function(e) {
			var contents = $(this).contents(), gridField = contents.find('.ss-gridfield');
			contents.find('table.ss-gridfield').css('margin-top', 0);
			contents.find('input[name=action_doAttach]').unbind('click.openSelectDialog').bind('click.openSelectDialog', function() {
				var ids = $.map(gridField.find('.ss-gridfield-item.ui-selected'), function(el) {return $(el).data('id');});

				self.requestFileTemplates(ids);
				dialog.ssdialog('close');

				return false;
			});
		});
		dialog.ssdialog('open');
	},

	requestFileTemplates: function (ids) {
		if(!ids || !ids.length) return;
		var self = this;
		$.ajax({
			url: this.getIFrameUrl()+'/filesbyid?ids='+ids.join(',') + '&' + this.getIFrameParams(),
			dataType: 'JSON',
			success: function (json) {
				json.forEach(function(item) {
					self.attachFile(item.id, item.html);
				});
			}
		});
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

$('.file-attachment-field-previews .file-icon, .file-attachment-field-previews .file-meta').entwine({
	onclick: function(e) {
		e.preventDefault();

		var parent = $(this).parents('li');

		window.open(parent.data('file-link') , '_blank');
	}
})

})(jQuery);
