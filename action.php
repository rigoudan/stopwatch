<?php
/**
 * DokuWiki Markdown External Image (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  njj <niejijing@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_LF')) define('DOKU_LF', "\n");
if(!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

require_once DOKU_PLUGIN . 'action.php';

class action_plugin_stopwatch extends DokuWiki_Action_Plugin {

    private $tempdir  = '';
    private $tempfile = '';

    public function register(Doku_Event_Handler $controller) {

        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call_unknown');

    }

    public function handle_ajax_call_unknown(Doku_Event &$event, $param) {
        if($event->data != 'plugin_stopwatch') return;

        global $INPUT;
		// cmd
        $cmd = $INPUT->post->str('cmd');
		// watch id
		$wid = $INPUT->post->str('wid');
		// time stamp
		$ts = $INPUT->post->str('ts');
		
        if(!$cmd) $this->fail(400, 'e_nodata');
		
		if($cmd == 'fetch') {
			// mediaFN + file_exists + io_readfile, refer to detail.php
			// use fetch.php is the most cheap way.
			$filename = $this->getConf('filename');
			$filename = str_replace(
                        array(
                             '@NS@',
                             '@ID@',
                             '@USER@'
                        ),
                        array(
                             getNS($INPUT->post->str('id')),
                             $INPUT->post->str('id'),
                             $_SERVER['REMOTE_USER']
                        ),
                        $filename
            );
			$filename .= '_'.$wid.'.doc';
			$filename = cleanID($filename);
			$SRC = mediaFN($filename);
			if(!file_exists($SRC)){
				//doesn't exist!
				$data = "0";
			}
			else {
				$data = file_get_contents($SRC);
			}
			//$data = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/lib/exe/fetch.php?media='.$filename);
			//Still here? We had a successful fetch
			header('Content-Type: application/json');
			$json = new JSON();
			echo $json->encode(
				array(
					'message' => 'fetchsucc',
					'ts' => $data
				)
			);
		}
		else if($cmd == 'store') {
			$tempname = $this->storetemp($ts);
			$filename = $this->getConf('filename');
			$filename = str_replace(
                        array(
                             '@NS@',
                             '@ID@',
                             '@USER@'
                        ),
                        array(
                             getNS($INPUT->post->str('id')),
                             $INPUT->post->str('id'),
                             $_SERVER['REMOTE_USER']
                        ),
                        $filename
            );
			$filename .= '_'.$wid.'.doc';
			$filename = cleanID($filename);
			// check ACLs
			$auth = auth_quickaclcheck($filename);
			if($auth < AUTH_UPLOAD) $this->fail(403, 'uploadfail');
			// do the actual saving
			$result = media_save(
						array(
							 'name' => $tempname,
							 'mime' => "doc",
							 'ext'  => ".doc"
						),
						$filename,
						true,
						$auth,
						'copy'
			);
			if(is_array($result)) $this->fail(500, 'lueluelue');

			//Still here? We had a successful store
			$this->clean();
			header('Content-Type: application/json');
			$json = new JSON();
			echo $json->encode(
				array(
					'message' => 'storesucc',
					'ts' => '0'
				)
			);
		}
		
        $event->preventDefault();
        $event->stopPropagation();
    }

    /**
     * Create a temporary file from the given data
     *
     * exits if an error occurs
     *
     * @param $data
     * @return string
     */
    private function storetemp($data){
        // store in temporary file
        $this->tempdir  = io_mktmpdir();
        if(!$this->tempdir) $this->fail(500);
        $this->tempfile = $this->tempdir.'/'.md5($data);
        if(!io_saveFile($this->tempfile, $data)) $this->fail(500);
        return $this->tempfile;
    }

    /**
     * remove temporary file and directory
     */
    private function clean(){
        if($this->tempfile && file_exists($this->tempfile)) @unlink($this->tempfile);
        if($this->tempdir && is_dir($this->tempdir)) @rmdir($this->tempdir);
        $this->tempfile = '';
        $this->tempdir = '';
    }

    /**
     * End the execution with a HTTP error code
     *
     * Calls clean
     *
     * @param int $status HTTP status code
     * @param string $text
     */
    private function fail($status, $text=''){
        $this->clean();
        http_status($status, $text);
        exit;
    }
}

// vim:ts=4:sw=4:et:
