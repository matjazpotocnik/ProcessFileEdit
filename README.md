# ProcessFileEdit

Allow editing of files on filesystem. The list of directories and files are
displayed, clicking on file opens modal window with codemirror editor. Intended
primarily for superusers, give other users file-edit permission.

### Directory path
Path to the directory from which to display directory tree. By default it's set
to $config->paths->site but it can be overriden. Be carefull not to set it too
"high", like setting it to the root of file system for exmples, since creating
directory structure can take too much time and script will time out.

### Extensions Filter
Comma separated list of extensions to filter files by. By default "js,css,php,module".

### Include or exclude extensions
Check this to exclude the extensions defined in Extension Filter, leave unchecked
to include them. By default, this is unchecked so files matching extensions will be
displayed in directory/file tree.

### Editor Height
The height of the editor textarea, default is "auto", can be any height like "450px".

### Installation
Copy the files to the /site/modules/ProcessFileEdit folder, log in to your ProcessWire
admin and go to the Modules page. Click the Refresh button and then Install. More info
at http://modules.processwire.com/install-uninstall/

### License
Copyright (c) 2016 Florea Banus George (https://github.com/f-b-g-m/ProcessFileEdit).  
Fork by Matja&#x17E; Poto&#x10D;nik (https://github.com/matjazpotocnik/ProcessFileEdit). 

Licensed under the MIT license. See the LICENSE file for details.
