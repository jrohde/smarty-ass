# Smarty 3+ plugin to bundle and minify js and/or css files.

This Smarty plugin uses [Matthias Mullie's ' Minify](https://github.com/matthiasmullie/minify) library to do it's minifications and bundling.

It's automatically checks for (JS/CSS) file changes and then rebuild's the minified/bundled output file.


**Table of Contents**

1.  [Installation](#install)
2.  [Paremeters & Usage](#usage)


<a name="install"></a>

### Installation

Use [Composer](http://getcomposer.org/) to include the library into your project:

    $ composer.phar require jrohde/smarty-ass

Now add the plugin dir to your Smarty instance:

```
$smarty = new \Smarty();
$smarty->addPluginsDir('./path/to/vendor/rohdej/smarty-ass/');
```

You can check if the correct path was added by using:

```
var_dump($smarty->getPluginsDir());
```


<a name="usage"></a>

### Parameters & Usage


## Parameters

- **in** - array, absolute path('s) to js OR css files.
- **out** - string, absolute path to the minified and bundled file output (must be writable by the webserver/php user!). This parameter is optional and defaults to '/assets/(js/css)/${smarty_template_name}_combined.(css/js)'.
- **ttl** - integer, time to live of cached files. This is the maximum time a minified/combined file may exist before a complete rebuild. Note that when source files are modified the output file is regenerated, regardless of this value. This parameter is optional and defaults to '31536000' (a.k.a: 1 year).
- **gzip** - boolean, gzip's the output file. Make sure your PHP/Webserver supports this! This parameter is optional and defaults to 'false'
- **debug** - boolean, disables compilation and just output all as normal if set to: 'true'. This parameter is optional and defaults to 'false'


## Usage (in Smarty 3+ templates)

```
{ass in=['file1.js','file2.js'] out='/assets/combined_and_minified.js' ttl='3600' gzip='false' debug=false}
```

You may also use external/cdn's like so:

```
{ass in=[
    'https://code.jquery.com/jquery-git.min.js',
    'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js'
] out='/assets/combined_and_minified.js' ttl='3600' gzip='false' debug=false}
```

Use inline JS or Javscript (make sure to escape single/double quotes where needed!):

```
{ass in=["
    <script>
    var cool = 'awesome';
    </script>
"] out='/assets/combined_and_minified.js' ttl='3600' gzip='false' debug=false}
```

Or even mix it all together:

```
{ass in=[
    'https://code.jquery.com/jquery-git.min.js',
    'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js',
    'file1.js',
    'file2.js',
    '<script>
        var cool = \'awesome\';
    </script>'
] out='/assets/combined_and_minified.js' ttl='3600' gzip='false' debug=false}
```
