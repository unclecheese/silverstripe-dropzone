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
* No jQuery dependency

## Screenshots

#### Grid view
![Screenshot](https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen1.png)

#### List view
![Screenshot](https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen2.png)

#### Remove/delete files
![Screenshot](https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen3.png)

#### Beautiful error handling
![Screenshot](https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen4.png)

#### Any thumbnail size you like

![Screenshot](https://raw.githubusercontent.com/unclecheese/silverstripe-dropzone/master/images/screenshots/screen5.png)

## Usage
The API is the same as a `FileField`, only with more options.

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
  ->setThumbanilWidth(180)
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

### Default settings

Default values for most settings can be found in the `config.yml` file included with the module.

## Usage in the CMS

`FileAttachmentField` can be used as a replacement for `UploadField` in the CMS.

## Troubleshooting

Ring Uncle Cheese.
