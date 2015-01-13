<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file Requirements.php
 * @author Ambroise Maupate
 */

namespace RZ\Roadiz\Console\Tools;

/**
* Requirements class
*/
class Requirements
{
    protected $totalChecks = 0;
    protected $successChecks = 0;

    /**
     * @return boolean
     */
    public function isTotalSuccess()
    {
        return $this->totalChecks <= $this->successChecks;
    }

    /**
     * @return array $checks
     */
    public function getRequirements()
    {
        $checks = array();

        $checks['php_version'] = array(
            'status'=>$this->testPHPVersion('5.4'),
            'version_minimum' => '5.4',
            'found' => phpversion(),
            'message' => 'Your PHP version is outdated, you must update it.'
        );
        if ($checks['php_version']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['session'] = array(
            'status'=>$this->testExtension('session'),
            'extension' => true,
            'message' => 'You must enable PHP sessions.'
        );
        if ($checks['session']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['json'] = array(
            'status'=>$this->testExtension('json'),
            'extension' => true,
            'message' => 'JSON library is needed for configuration handling.'
        );

        if ($checks['json']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['zip'] = array(
            'status'=>$this->testExtension('zip'),
            'extension' => true,
            'message' => 'ZIP extension is needed.'
        );

        if ($checks['zip']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['date'] = array(
            'status'=>$this->testExtension('date'),
            'extension' => true,
            'message' => 'Date extension is needed.'
        );

        if ($checks['date']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['gd'] = array(
            'status'=>$this->testExtension('gd'),
            'extension' => true,
            'message' => 'GD library must be installed.'
        );

        if ($checks['gd']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['curl'] = array(
            'status'=>$this->testExtension('curl'),
            'extension' => true,
            'message' => 'cUrl extension is needed for API requests.'
        );

        if ($checks['curl']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['memory_limit'] = array(
            'status'=>$this->testPHPIntValue('memory_limit', '64M'),
            'value_minimum' => '64M',
            'found' => ini_get('memory_limit'),
            'message' => 'Your PHP configuration has a too low value for “upload_max_filesize”'
        );

        if ($checks['memory_limit']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['post_max_size'] = array(
            'status'=>$this->testPHPIntValue('post_max_size', '16M'),
            'value_minimum' => '16M',
            'found' => ini_get('post_max_size'),
            'message' => 'Your PHP configuration has a too low value for “post_max_size”'
        );

        if ($checks['post_max_size']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['upload_max_filesize'] = array(
            'status'=>$this->testPHPIntValue('upload_max_filesize', '16M'),
            'value_minimum' => '16M',
            'found' => ini_get('upload_max_filesize'),
            'message' => 'Your PHP configuration has a too low value for “upload_max_filesize”'
        );

        if ($checks['upload_max_filesize']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['project_folder_writable'] = array(
            'status'=>$this->folderWritable(ROADIZ_ROOT),
            'folder' => ROADIZ_ROOT,
            'mod' => fileperms(ROADIZ_ROOT),
            'message' => 'Project folder is not writable by PHP, you must change its permissions.'
        );

        if ($checks['project_folder_writable']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['files_folder_writable'] = array(
            'status'=>$this->folderWritable(ROADIZ_ROOT.'/files'),
            'folder' => ROADIZ_ROOT.'/files',
            'mod' => fileperms(ROADIZ_ROOT.'/files'),
            'message' => 'Storage folder is not writable by PHP, you must change its permissions.'
        );

        if ($checks['files_folder_writable']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        return $checks;
    }

    /**
     * @param string  $name
     * @param integer $expected
     *
     * @return boolean
     */
    protected function testPHPIntValue($name, $expected)
    {

        $intValue = (int) (str_replace(array('s','K','M','G'), array('','','',''), ini_get($name)));
        if ($intValue < $expected) {
            return false;
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    protected function methodExists($name)
    {
        return (function_exists($name) === true) ? (true) : (false);
    }

    /**
     * @param string $filename
     *
     * @return boolean
     */
    protected function folderWritable($filename)
    {
        return is_writable($filename) === true ? true : false;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    protected function testExtension($name)
    {
        return extension_loaded($name);
    }

    /**
     * @param integer $version
     *
     * @return integer
     */
    protected function testPHPVersion($version)
    {
        return !version_compare(phpversion(), $version, '<');
    }
}
