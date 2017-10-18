# ProcessFileEdit

Allow modal editing of files on filesystem. The list of directories and files
are displayed, clicking on file opens modal window with codemirror editor. If
file is an image, viewer is opened in modal. If file is used as a template, link
to the template edit is provided. Intended primarily for superusers, give other
users file-edit permission.

This is not the replacement for full blown editor. Be aware that if you make
changes to files that cause PW to throws an error in admin, you loose access to
this module too and you will need some other means to access the file again!

## Options

### Directory path
Path to the directory from which to display directory tree. By default it's set 
to $config->paths->site

### File encoding
Encoding used when saving file name. Different operating systems and underlying
filesystem use different ways of how file names are stored on the filesystem.
This setting allows you to overcome sitautions when file name become garbled after
save operation. Options are: 'Auto detect' (this is default), 'Windows-1250',
'Windows-1252', 'ISO-8859-2', 'PHP\'s urldecode' and 'none'.

### Line endings
Type of line endings to use when saving. CodeMirror returns end-of-line as
Windows style end-of-line (\r\n). Here you can set up how to handle this. Default is
'Auto detect' which detect original line endings and apply them on save. You
may manually set line endings to Windows (\r\n), Linux (\n) or Mac (\r) style. 

### Extensions filter
Comma separated list of extensions to filter files by. By default "php,module,js,css".

### Include or exclude extensions
Select to include or exclude files based on the extensions defined in Extension
Filter. Include is default, so files matching extensions will be displayed in
directory/file tree.

### Backup extension
Extension to use when backing up edited file, for example ".bak" ("." is prepended if
omitted). Leave empty for no backup (this is default).

### Editor height
The height of the editor textarea, default is "auto", can be any height like "450px".

## Installation
Copy the files to the /site/modules/ProcessFileEdit folder, log in to your ProcessWire
admin and go to the Modules page. Click the Refresh button and then Install. More info
at http://modules.processwire.com/install-uninstall/

## License
Copyright (c) 2016 Florea Banus George (https://github.com/f-b-g-m/ProcessFileEdit).  
Fork by Matja&#382; Poto&#269;nik (https://github.com/matjazpotocnik/ProcessFileEdit).  
Big thanks to Roland Toth.  
Support forum: https://processwire.com/talk/topic/14276-file-editor/

Licensed under the MIT license. See the LICENSE file for details.
