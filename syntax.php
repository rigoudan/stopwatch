<?php
/**
 * Add stop watch
 *
 * by njj
 * under the terms of the GNU GPL v2.
 *
 * @license    GNU_GPL_v2
 * @author     njj <niejijing@qq.com>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');


class syntax_plugin_stopwatch extends DokuWiki_Syntax_Plugin {

    function getType(){ return 'container'; }
    function getPType(){ return 'block'; }
    function getAllowedTypes() { 
        return array('container','substition','protected','disabled','formatting','paragraphs');
    }

    function getSort(){ return 195; }

    // override default accepts() method to allow nesting 
    // - ie, to get the plugin accepts its own entry syntax
    function accepts($mode) {
      if ($mode == substr(get_class($this), 7)) return true;
        return parent::accepts($mode);
      }

    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<stopwatch>.*?(?=</stopwatch>)',$mode,'plugin_stopwatch');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</stopwatch>','plugin_stopwatch');
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {

        switch ($state) {

          case DOKU_LEXER_ENTER : 
			$watchid = trim(substr($match,11));
			return array($state, $watchid);     
 
          case DOKU_LEXER_UNMATCHED :
            return array($state, $match);
        
          default:
            return array($state);
        }
    }

    function render($mode, Doku_Renderer $renderer, $indata) {

        if($mode == 'xhtml'){

          list($state, $data) = $indata;

          switch ($state) {
            case DOKU_LEXER_ENTER :
              $renderer->doc .= '<div class="stopwatch" id="stopwatch-'.$data.'">';
			  $renderer->doc .= '<div class="elapsed">00:00:00</div>';
			  $renderer->doc .= '<div class="millisecond">0</div>';
			  $renderer->doc .= '<div><div class="btn"><a class="start" href="javascript:void(0);">Start</a></div><div class="btn"><a class="clear" href="javascript:void(0);">Clear</a></div></div>';
              break;
  
            case DOKU_LEXER_UNMATCHED :
              $renderer->doc .= $renderer->_xmlEntities($data);
              break;
  
            case DOKU_LEXER_EXIT :
              $renderer->doc .= "\n</div>";
              break;
          }
          return true;

        }
        
        // unsupported $mode
        return false;
    }
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
