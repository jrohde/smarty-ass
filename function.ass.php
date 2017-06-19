<?php

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty CSS/JS bundle and minify function plugin
 *
 * Type:    function<br>
 * Name:    ass<br>
 * Date:    June 13, 2017
 * Purpose: Bundle and minify JS and/or CSS files
 * Input:   string to ass
 * Example: {ass input=['file1.js','file2.js'] output='/assets/combined.js' age='3600'}
 *
 * @author Joost Rohde <j.rohde@nederland.live>
 * @basedOn https://github.com/dead23angel/smarty-combine
 * @version 1.0
 * @param array
 * @param string
 * @param int
 * @return string
 */

use tubalmartin\CssMin\Minifier as CSSmin;
use JShrink\Minifier as JSmin;

function smarty_function_ass($params, &$smarty)
{
    /**
     * Build combined file
     *
     * @param array $params
     */
    if ( ! function_exists('smarty_build_ass')) {
        function smarty_build_ass($params)
        {
            $filelist = array();
            $lastest_mtime = 0;

            foreach ($params['input'] as $item) {
                $mtime = filemtime(getenv('DOCUMENT_ROOT') . $item);
                $lastest_mtime = max($lastest_mtime, $mtime);
                $filelist[] = array('name' => $item, 'time' => $mtime);
            }

            if ($params['debug'] == true) {
                $output_filename = '';
                foreach ($filelist as $file) {
                    if ($params['type'] == 'js') {
                        $output_filename .= '<script type="text/javascript" src="'.$file['name'].'" charset="utf-8"></script>' . "\n";
                    } elseif ($params['type'] == 'css') {
                        $output_filename .= '<link type="text/css" rel="stylesheet" href="'.$file['name'].'" />' . "\n";
                    }
                }

                echo $output_filename;
                return;
            }

            $last_cmtime = 0;

            if (file_exists(getenv('DOCUMENT_ROOT') . $params['cache_file_name'])) {
                $last_cmtime = file_get_contents(getenv('DOCUMENT_ROOT') . $params['cache_file_name']);
            }

            if ($lastest_mtime > $last_cmtime) {
                $glob_mask = preg_replace('/\.(js|css)$/i', '_*.$1', $params['output']);
                $files_to_cleanup = glob(getenv('DOCUMENT_ROOT') . $glob_mask);

                foreach ($files_to_cleanup as $cfile) {
                    if (is_file($cfile) && file_exists($cfile)) {
                        unlink($cfile);
                    }
                }

                $output_filename = preg_replace('/\.(js|css)$/i', date('_YmdHis.', $lastest_mtime) . '$1', $params['output']);

                $dirname = dirname(getenv('DOCUMENT_ROOT') . $output_filename);

                if ( ! is_dir($dirname)) {
                    mkdir($dirname, 0755, true);
                }

                $fh = fopen(getenv('DOCUMENT_ROOT') . $output_filename, 'a+');

                if (flock($fh, LOCK_EX)) {
                    foreach ($filelist as $file) {
                        $min = '';

                        if ($params['type'] == 'js') {
                            $compressor = new JSmin();
                            $min = JSmin::minify(file_get_contents(getenv('DOCUMENT_ROOT') . $file['name']), array('flaggedComments' => false));
                        } elseif ($params['type'] == 'css') {
                            $compressor = new CSSmin(false);
                            $min = $compressor->run(file_get_contents(getenv('DOCUMENT_ROOT') . $file['name']));
                        } else {
                            fputs($fh, PHP_EOL . PHP_EOL . '/* ' . $file['name'] . ' @ ' . date('c', $file['time']) . ' */' . PHP_EOL . PHP_EOL);
                            $min = file_get_contents(getenv('DOCUMENT_ROOT') . $file['name']);
                        }
                        fputs($fh, $min);
                    }

                    flock($fh, LOCK_UN);
                    file_put_contents(getenv('DOCUMENT_ROOT') . $params['cache_file_name'], $lastest_mtime, LOCK_EX);
                }

                fclose($fh);
                clearstatcache();
            }

            touch(getenv('DOCUMENT_ROOT') . $params['cache_file_name']);
            smarty_print_out($params);
        }
    }

    /**
     * Print filename
     *
     * @param string $params
     */
    if ( ! function_exists('smarty_print_out')) {
        function smarty_print_out($params)
        {
            $last_mtime = 0;

            if (file_exists(getenv('DOCUMENT_ROOT') . $params['cache_file_name'])) {
                $last_mtime = file_get_contents(getenv('DOCUMENT_ROOT') . $params['cache_file_name']);
            }

            $output_filename = preg_replace('/\.(js|css)$/i', date('_YmdHis.', $last_mtime) . '$1', $params['output']);

            if ($params['type'] == 'js') {
                echo '<script type="text/javascript" src="' . $output_filename . '" charset="utf-8"></script>';
            } elseif ($params['type'] == 'css') {
                echo '<link type="text/css" rel="stylesheet" href="' . $output_filename . '" />';
            } else {
                echo $output_filename;
            }
        }
    }

    if ( ! isset($params['input'])) {
        trigger_error('input cannot be empty', E_USER_NOTICE);
        return;
    }

    if ( ! is_array($params['input']) || count($params['input']) < 1) {
        trigger_error('input must be array and have at least one item', E_USER_NOTICE);
        return;
    }

    foreach ($params['input'] as $file) {
        if ( ! file_exists(getenv('DOCUMENT_ROOT') . $file)) {
            trigger_error('File ' . getenv('DOCUMENT_ROOT') . $file . ' does not exist!', E_USER_WARNING);
            return;
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if ( ! in_array($ext, array('js', 'css'))) {
            trigger_error('all input files must have js or css extension', E_USER_NOTICE);
            return;
        }

        $files_extensions[] = $ext;
    }

    if (count(array_unique($files_extensions)) > 1) {
        trigger_error('all input files must have the same extension', E_USER_NOTICE);
        return;
    }

    $params['type'] = $ext;

    if ( ! isset($params['output'])) {
        //$params['output'] = dirname($params['input'][0]) . '/combined.' . $ext;
        //$params['output'] = '/assets/combined.' . $ext;
        $params['output'] = '/assets/'.$_SERVER['REQUEST_URI'].'/combined.' . $ext;
    }

    if ( ! isset($params['age'])) {
        $params['age'] = 31536000; // looooong caching
    }

    if ( ! isset($params['cache_file_name'])) {
        $params['cache_file_name'] = $params['output'] . '.cache';
    }

    if ( ! isset($params['debug'])) {
        $params['debug'] = DEBUG;
    }

    $cache_file_name = $params['cache_file_name'];

    if ($params['debug'] == true || ! file_exists(getenv('DOCUMENT_ROOT') . $cache_file_name)) {
        smarty_build_ass($params);
        return;
    }

    $cache_mtime = filemtime(getenv('DOCUMENT_ROOT') . $cache_file_name);

    if ($cache_mtime + $params['age'] < time()) {
        smarty_build_ass($params);
    } else {
        smarty_print_out($params);
    }
}
