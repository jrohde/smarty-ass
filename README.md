# Smarty 3+ plugin to bundle and minify js and/or css files.

This Smarty plugin uses the follwing libraries to do it's minifications:

*For CSS: https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port
*For JS: https://github.com/tedious/JShrink

**Table of Contents**

1.  [Installation](#install)
2.  [Usage & parameters](#usage)


<a name="install"></a>

### Installation

Use [Composer](http://getcomposer.org/) to include the library into your project:

    $ composer.phar require jrohde/smarty-ass

Now add the plugin dir to your Smarty instance:

```$smarty = new \Smarty();
$smarty->addPluginsDir('./path/to/vendor/rohdej/smarty-ass/');```

You can check if the correct path was added by using:

```var_dump($smarty->getPluginsDir());```


<a name="usage"></a>

### Usage & parameters

## Usage (in Smarty 3+ templates)

```{ass input=['file1.js','file2.js') output='/assets/combined_and_minified.js' age='3600' debug=false}```

## Parameters

* **input** - array with absolute path to js OR css files (don't mix them!).
* **output** - (optional) absolute path to output file (writable by the webserver/php). If not given it will
* **age** - (optional) TTL of cached files. Default is 31536000 - 1 year.
* **debug** - (optional) parameter in the value of TRUE, disable compilation useful for debugging when developing a site.. By default it is FALSE. You can omit this parameter.
