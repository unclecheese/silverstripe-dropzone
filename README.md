# Dropzone for SilverStripe
#### Upload with sanity.

## Introduction
The Dropzone module provides `FileAttachmentField`, a robust HTML5 uploading interfaces for SilverStripe, allowing forms to save file uploads to `DataObject` instances.

## Features
* Upload on the frontend, or in the CMS, with one consistent interface
* Drag-and-drop uploading
* Automatic client-side thumbnailing
* Grid view / List view
* Upload progress
* Limit file count, file size, file type
* Permissions for removing/deleting files
* Tracking files (remove uploaded files that aren't attached to anything)
* No jQuery dependency

## Screenshots

#### Grid view
<img src="https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen1.png" width="75%">


#### List view
<img src="https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen2.png" width="75%">

#### Remove/delete files
<img src="https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen3.png" width="75%">

#### Beautiful error handling
<img src="https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen4.png" width="75%">

#### Any thumbnail size you like

<img src="https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen5.png" width="75%">

## Usage
The field instantiates similarly to `UploadField`, taking the name of the file relationship and a label, as the first two arguments. Once instantiated, there are many ways to configure the UI.

```php
FileAttachmentField::create('MyFile', 'Upload a file')
  ->setView('grid')
```

If the form holding the upload field is bound to a record, (i.e. with `loadDataFrom()`), the upload field will automatically allow multiple files if the relation is a `has_many` or `many_many`. If the form is not bound to a record, you can use `setMultiple(true)`.

Image-only uploads can be forced using the `imagesOnly()` method. If the form is bound to a record, and the relation points to an `Image` class, this will be automatically set.

### More advanced options

```php
FileAttachmentField::create('MyFiles', 'Upload some  files')
  ->setThumbnailHeight(180)
  ->setThumbnailWidth(180)
  ->setAutoProcessQueue(false) // do not upload files until user clicks an upload button
  ->setMaxFilesize(10) // 10 megabytes. Defaults to PHP's upload_max_filesize ini setting
  ->setAcceptedFiles(array('.pdf','.doc','.docx'))
  ->setPermissions(array(
    'delete' => false,
    'detach' => function () {
      return Member::currentUser() && Member::currentUser()->inGroup('editors');
    }
  ));
```

Image uploads get a few extra options.

```php
FileAttachmentField::create('MyImage','Upload an image')
    ->imagesOnly() // If bound to a record, with a relation to 'Image', this isn't necessary.
    ->setMaxResolution(50000000); // Do not accept images over 5 megapixels
```

### Default settings

Default values for most settings can be found in the `config.yml` file included with the module.

## Usage in the CMS

`FileAttachmentField` can be used as a replacement for `UploadField` in the CMS.

## Interacting with the Dropzone interface programatically

For custom integrations, you may want to access the `UploadInterface` object that manages the upload UI (see `file_attachment_field.js`). You can do that one of two ways:
* If you have jQuery installed, simply access the `dropzoneInterface` data property of the `.dropzone` element
```js
$('#MyFileDropzone').data('dropzoneInterface').clear();
```

* If you are not using jQuery, the `UploadInterface` object is injected into the browser global `window.dropzones`, indexed by the id of your `.dropzone` element. 
```js
window.dropzones.MyFileDropzone.clear();
```
**NB**: The ID of the actual `.dropzone` element by default is the name of the form input, with 'Dropzone' appended to it, so `FileAttachmentField::create('MyFile')` creates a dropzone with an ID of 'MyFileDropzone'

# Tracking / removing unused file uploads

```php
FileAttachmentField::create('MyImage','Upload an image')
    ->setTrackFiles(true)
```
or:
```yml
FileAttachmentField:
  track_files: true
```

To stop users from uploading lots of files and filling the servers hard-drive via the frontend, you can track each file upload in a record, which is then removed when a form saves using `Form::saveInto($record)`.

If you do not use `Form::saveInto`, you will need to manually untrack the file IDs with:
```
FileAttachmentFieldTrack::untrack($data['MyImageID']);
```

To action the deletion of all the tracked files, you can run the `FileAttachmentFieldCleanTask`.

## Troubleshooting

 * When using `FileAttachmentField` with `BootstrapForm` be sure to [ignore it from the bootstrap transformation](https://github.com/unclecheese/silverstripe-bootstrap-forms/blob/master/code/BootstrapFieldList.php#L74).

Ring Uncle Cheese.
