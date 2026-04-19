# ProcessFileEdit

Module for the ProcessWire CMS that allows modal editing of files on the filesystem.  
The list of directories and files is displayed; clicking on a file opens a modal
window with the CodeMirror editor. If the file is an image, the viewer is opened
in a modal window. If the file is used as a template, a link to the template edit
page is provided.

This module is intended primarily for superusers, but you may give other users
file‑editing permission.

This is **not a replacement for a full‑blown editor**. Be aware that if you make
changes to files that cause ProcessWire to throw an error in the admin, you will
lose access to this module as well and will need some other way to access and
fix the file.

---

## Options

### Directory path
The path to the directory from which the directory tree is displayed. By default,
it is set to `$config->paths->site`.

### Line endings
The type of line endings to use when saving files. CodeMirror returns end‑of‑line
characters as Windows‑style line endings (`\r\n`). Here you can configure how
these are handled.

The default is **Auto detect**, which tries to detect the original line endings
and apply them on save. You may also manually set line endings to Windows (`\r\n`),
Linux (`\n`), or Mac (`\r`) style.

### Extensions filter
A comma‑separated list of extensions to filter files by. The default is
`php,module,js,css`.

### Include or exclude extensions
Choose whether to include or exclude files based on the extensions defined in
the Extensions Filter. **Include** is the default, meaning that files matching
the configured extensions will be displayed in the directory/file tree.

### Backup extension
The extension to use when backing up an edited file, for example `.bak`
(`.` is automatically prepended if omitted). Leave empty to disable backups
(this is the default).

### Dotfiles exclusion
Check this option to exclude files and folders starting with a dot (`.`), such as
`.gitignore`, `.htaccess`, etc. The default is unchecked.

This option also hides old versions of site modules created by Ryan’s *Upgrades*
module when browsing `/site/modules`. You can add `htaccess` or other extensions
to the Extensions Filter to allow those files.

### Editor height
The height of the editor textarea. The default is `auto`, but you can specify a
fixed height such as `450px`.

### Line wrapping
Wrap long lines in the editor. Enabled by default.

### CodeMirror theme
A list of themes supported by CodeMirror. See the
[demo](https://codemirror.net/demo/theme.html) for available themes.
The default theme is `default`.

---

## Installation

Copy the files to `/site/modules/ProcessFileEdit`, log in to your ProcessWire
admin, and go to the **Modules** page. Click the **Refresh** button and then
**Install**.

More information:
<http://modules.processwire.com/install-uninstall/>

---

## License

Copyright (c) 2016 Florea Banus George  
<https://github.com/f-b-g-m/ProcessFileEdit>

Fork by Matjaž Potočnik  
<https://github.com/matjazpotocnik/ProcessFileEdit>

Big thanks to Roland Toth.

Support forum:  
<https://processwire.com/talk/topic/14276-file-editor/>

Licensed under the MIT license. See the LICENSE file for details.
