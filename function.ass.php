<?php

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty CSS/JS bundle and minify function plugin
 *
 * Type:    function
 * Name:    ass
 * Date:    June 21, 2017
 * Purpose: Bundle and minify JS and/or CSS files
 * Input:   string to ass
 * Example: {ass in=['file1.js','file2.js'] out='/assets/combined.js' gzip='true' ttl='3600' debug='false'}
 *
 * @author Joost Rohde <j.rohde@nederland.live>
 * @version 1.0.1
 * @param array
 * @param string
 * @param int
 * @return string
 */

use MatthiasMullie\Minify;

function smarty_function_ass(array $params, Smarty_Internal_Template $template)
{

    # defaults
    $ttl   = 31536000;
    $gzip  = false;
    $debug = defined('DEBUG') ? DEBUG : false;


    if ( ! function_exists('smarty_build_ass')) {
        function smarty_build_ass($params)
        {
            # cleanup
            $old = preg_replace('/\.(js|css)$/i', '_*.$1', $params['out']);
            $clean = glob($params['doc_root'].$old);
            foreach ($clean as $cf) {
                if (is_file($cf)) { unlink($cf); }
            }
            # prepare output
            $timestamp = time();
            $params['ass_file'] = preg_replace('/\.(js|css)$/i','_'.$timestamp.'.$1', $params['out']);
            $dirname = dirname($params['doc_root'] . $params['ass_file']);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0755, true);
            }
            # minify and bundle
            $context = stream_context_create(array('http' => array('user_agent' => $_SERVER['HTTP_USER_AGENT'], 'ignore_errors' => true)));
            $dr = $params['doc_root'];
            $assets = array_map(function($v) use($dr) {
                if($v['type'] == 'file') {
                    $v['src'] = $dr.$v['src'];
                } elseif ($v['type'] == 'url') {
                    $v['src'] = file_get_contents($v['src'], false, $context);
                }
                return $v['src'];
            }, $params['assets']);
            if ($params['type'] == 'js') {
                $minifier = new Minify\JS($assets);
            } elseif ($params['type'] == 'css') {
                $minifier = new Minify\CSS($assets);
            }
            unset($assets);
            # save minified and bundled file to disk
            if($params['gzip']) {
                $minifier->gzip($params['doc_root'] . $params['ass_file']);
            } else {
                $minifier->minify($params['doc_root'] . $params['ass_file']);
            }
            # save the new mtime cache file to disk
            $cache = array_map(function($v) {
                if ($v['type'] == 'inline') { unset($v['src']); }
                return $v;
            }, $params['assets']);
            $cache['timestamp'] = $timestamp;
            file_put_contents($params['doc_root'] . $params['cache_file'], json_encode($cache), LOCK_EX);
            # output
            smarty_print_ass($params);
        }
    }

    /*
     * name: smarty_print_ass
     * @param array
     * @return string
     *
     */
    if (!function_exists('smarty_print_ass')) {
        function smarty_print_ass($params) {
            if ($params['type'] == 'js') {
                echo '<script type="text/javascript" src="'.$params['ass_file'].'" charset="utf-8"></script>';
            } elseif ($params['type'] == 'css') {
                echo '<link type="text/css" rel="stylesheet" href="'.$params['ass_file'].'" />';
            }
        }
    }

    /*
     * name: smarty_debug_ass
     * @param array
     * @return string
     *
     */
    if (!function_exists('smarty_debug_ass')) {
        function smarty_debug_ass($params) {
            $output = '';
            foreach ($params['assets'] as $k => $v) {
                if ($params['type'] == 'js') {
                    if($v['type'] == 'inline') {
                        $output .= "<script type='text/javascript' charset='utf-8'>\n".$v['src']."\n</script>"."\n";
                    } else {
                        $output .= '<script type="text/javascript" src="'.$v['src'].'" charset="utf-8"></script>'."\n";
                    }
                } elseif ($params['type'] == 'css') {
                    if($v['type'] == 'inline') {
                        $output .= "<style>\n".$v['src']."\n</style>"."\n";
                    } else {
                        $output .= '<link type="text/css" rel="stylesheet" href="'.$v['src'].'" />'."\n";
                    }
                }
            }
            echo $output;
            return;
        }
    }

    # set environment parameters
    $params['doc_root'] = getenv('DOCUMENT_ROOT');
    # start parameter 'in' checks
    if (!isset($params['in'])) {
        trigger_error('input cannot be empty', E_USER_NOTICE);
        return;
    }
    if (!is_array($params['in']) || count($params['in']) < 1) {
        trigger_error('input must be array and have at least one item', E_USER_NOTICE);
        return;
    }
    # validate and cleanup input
    $params['assets'] = $extensions = [];
    $tag_count = $url_count = 0;
    foreach ($params['in'] as $file) {
        # check for external files
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            $ext = pathinfo(parse_url($file,PHP_URL_PATH),PATHINFO_EXTENSION);
            if (!in_array($ext, array('js', 'css'))) {
                trigger_error('all input files must have js or css extension', E_USER_NOTICE);
                return;
            }
            $extensions[] = $ext;
            $params['assets'][] = [
                'type' => 'url',
                'src' => $file
            ];
        } elseif (preg_match('/.*<(script|style).*?>(.*?)<\/(script|style)>.*/ius', $file, $matches)) {
            $extensions[] = ($matches[1] == 'script') ? 'js' : 'css';
            $params['assets'][] = [
                'type' => 'inline',
                'hash' => md5($file),
                'src' => trim(preg_replace('/.*(<script|style).*?>(.*?)<\/(script|style)>.*/ius', '$2', $file))
            ];
        } elseif (!file_exists($params['doc_root'] . $file)) {
            trigger_error('File '.$params['doc_root'].$file.' does not exist!', E_USER_WARNING);
            return;
        } else {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (!in_array($ext, array('js', 'css'))) {
                trigger_error('all input files must have js or css extension', E_USER_NOTICE);
                return;
            }
            $extensions[] = $ext;
            $params['assets'][] = [
                'type' => 'file',
                'src' => $file,
                'mtime' => filemtime($params['doc_root'].$file)
            ];
        }
    }
    unset($params['in']);
    # start extension check
    if (count(array_unique($extensions)) > 1) {
        trigger_error('all input files must have the same extension', E_USER_NOTICE);
        return;
    }
    $params['type'] = $ext;
    # start parameter 'debug' checks
    if (!isset($params['debug'])) {
        $params['debug'] = $debug;
    }
    if ($params['debug'] == true) {
        smarty_debug_ass($params);
        return;
    }
    # start parameter 'out' checks
    if (!isset($params['out'])) {
        $s = $template->getTemplateDir();
        array_push($s, 'file:','.html');
        $params['out'] = '/assets/'.$ext.'/'.str_replace(array_reverse($s), [], $template->getTemplateResource()).'/combined.' . $ext;
    }
    # start parameter 'ttl' check
    if (!isset($params['ttl'])) {
        $params['ttl'] = $ttl; // looooong caching
    }
    # start parameter 'gzip' check
    if (!isset($params['gzip'])) {
        $params['gzip'] = $gzip;
    }
    # check for cache existence
    $build = true;
    $params['cache_file'] = $params['out'] . '.cache';
    if (file_exists($params['doc_root'].$params['cache_file'])) {
        $build = false;
        $cache = json_decode(file_get_contents($params['doc_root'].$params['cache_file']), true);
        # set initial output file based on cached timestamp
        $params['ass_file'] = preg_replace('/\.(js|css)$/i','_'.$cache['timestamp'].'.$1', $params['out']);
        unset($cache['timestamp']);
        # start checking if there are changes
        if(count($cache) !== count($params['assets'])) { // quick diff count check
            $build = true;
        } else { // array compare check
            $current = array_map(function ($v) {
                if ($v['type'] == 'inline') { unset($v['src']); }
                return $v;
            }, $params['assets']);
            if($cache !== $current) { $build = true; }
        }
        # check if past TTL (based on mtime of the cache file)
        if (filemtime($params['doc_root'] . $params['cache_file']) + $params['ttl'] < time()) {
            $build = true;
        }
    }
    # check what to do and do it!
    $build ? smarty_build_ass($params) : smarty_print_ass($params);
}
