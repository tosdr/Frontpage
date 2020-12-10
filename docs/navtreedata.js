/*
 @licstart  The following is the entire license notice for the JavaScript code in this file.

 The MIT License (MIT)

 Copyright (C) 1997-2020 by Dimitri van Heesch

 Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 and associated documentation files (the "Software"), to deal in the Software without restriction,
 including without limitation the rights to use, copy, modify, merge, publish, distribute,
 sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in all copies or
 substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING
 BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

 @licend  The above is the entire license notice for the JavaScript code in this file
*/
var NAVTREE =
[
  [ "CrispCMS Plugin API", "index.html", [
    [ "Developing Plugins", "devplugin.html", [
      [ "Directory Structure", "devplugin.html#intro", null ],
      [ "plugin.json - In detail", "devplugin.html#metadata", [
        [ "name - The name of your plugin", "devplugin.html#pluginname", null ],
        [ "description - The description of your plugin", "devplugin.html#plugindesc", null ],
        [ "hookFile - The name of your hook file", "devplugin.html#pluginhook", null ],
        [ "author - Your name, for credits", "devplugin.html#pluginauthor", null ],
        [ "onInstall - Configure your plugin on installation", "devplugin.html#plugininstall", [
          [ "createTranslationKeys - Creates translations when installing the plugin", "devplugin.html#plugininstalltranslation", null ],
          [ "createKVStorageItems - Creates config keys when installing the plugin", "devplugin.html#plugininstallstorage", null ],
          [ "activateDependencies - Activate other plugins when installing the plugin", "devplugin.html#plugininstalldep", null ]
        ] ],
        [ "onUninstall - Configuration for plugin removal", "devplugin.html#pluginuninstall", [
          [ "deleteData - Boolean to delete data on removal", "devplugin.html#pluginuninstalldata", null ],
          [ "deactivateDependencies - Deactivate other plugins when uninstalling the plugin", "devplugin.html#pluginuninstalldep", null ],
          [ "purgeDependencies - Deactivate other plugins when uninstalling the plugin", "devplugin.html#pluginuninstallpurge", null ]
        ] ]
      ] ],
      [ "The hook File", "devplugin.html#hookFileinfo", null ],
      [ "Creating API interface", "devplugin.html#createAPI", null ],
      [ "Creating and using crons", "devplugin.html#crons", [
        [ "Creating Crons", "devplugin.html#createcron", null ]
      ] ],
      [ "Adding Items to Navigation Bar", "devplugin.html#navbar", null ]
    ] ],
    [ "Deprecated List", "deprecated.html", null ],
    [ "Namespaces", "namespaces.html", [
      [ "Namespace List", "namespaces.html", "namespaces_dup" ]
    ] ],
    [ "Data Structures", "annotated.html", [
      [ "Data Structures", "annotated.html", "annotated_dup" ],
      [ "Data Structure Index", "classes.html", null ],
      [ "Class Hierarchy", "hierarchy.html", "hierarchy" ],
      [ "Data Fields", "functions.html", [
        [ "All", "functions.html", null ],
        [ "Functions", "functions_func.html", null ],
        [ "Variables", "functions_vars.html", null ]
      ] ]
    ] ],
    [ "Files", "files.html", [
      [ "File List", "files.html", "files_dup" ]
    ] ]
  ] ]
];

var NAVTREEINDEX =
[
"_cron_8php.html"
];

var SYNCONMSG = 'click to disable panel synchronisation';
var SYNCOFFMSG = 'click to enable panel synchronisation';