Smarty Combine
==============

Combine plugin allows concatenating several js or css files into one. 
It can be useful for big projects with a lot of several small CSS and JS files.

###Usage examples

**Template inline example Smarty 3**

```<script type="text/javascript" 
src="/{combine input=array('/js/core.js','/js/slideviewer.js') output='/js/big.js' age='30'}" 
charset="utf-8"></script>```

**Smarty 2 example**

**PHP code**

```$js_filelist = array('/js/core.js','/js/slideviewer.js');
$smarty_object->assign('js_files', $js_filelist);```

**Template code**

```<script type="text/javascript" 
src="/{combine input=$js_files output='/js/big.js' age='30'}" 
charset="utf-8"></script>```

**Plugin have 3 parametrs:**
**input** - must be an array, containing list with absolute pathes to files. In Smarty 3 it can be inline array, for Smarty 2 you will need to pass from yours controller a variable, which will contains this array.
**output** - absolute path to output file. Directory must be writable to www daemon (Usually chmod 777 resolve this problem :)
**age** - value of seconds between checks when original files were changed. By default it is 3600 - one hour. You can omit this parameter.