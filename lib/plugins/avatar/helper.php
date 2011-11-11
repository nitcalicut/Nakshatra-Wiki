<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Esther Brunner <wikidesign@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_avatar extends DokuWiki_Plugin {

  function getInfo() {
    return array(
      'author' => 'Gina Häußge, Michael Klier, Esther Brunner',
      'email'  => 'dokuwiki@chimeric.de',
      'date'   => '2008-03-02',
      'name'   => 'Avatar Plugin (helper class)',
      'desc'   => 'Functions to get info about comments to a wiki page',
      'url'    => 'http://wiki.splitbrain.org/plugin:avatar',
    );
  }
  
  function getMethods() {
    $result = array();
    $result[] = array(
      'name'   => 'getXHTML',
      'desc'   => 'returns the XHTML to display an avatar',
      'params' => array(
        'user or mail'     => 'string',
        'title (optional)' => 'string',
        'align (optional)' => 'string',
        'size (optional)'  => 'integer'),
      'return' => array('xhtml' => 'string'),
    );
    return $result;
  }
  
  /**
   * Returns the XHTML of the Avatar
   */
  function getXHTML($user, $title = '', $align = '', $size = NULL) {
    
    // determine the URL of the avatar image
    $src = $this->_getAvatarURL($user, $title, $size);
    
    // output with vcard photo microformat
    return '<img src="'.$src.'" class="media'.$align.' photo fn"'.
      ' title="'.$title.'" alt="'.$title.'" width="'.$size.'"'.
      ' height="'.$size.'" />';
  }
  
  /**
   * Main function to determine the avatar to use
   */
  function _getAvatarURL($user, &$title, &$size) {
    global $auth;
    
    if (!$size || !is_int($size)) $size = $this->getConf('size');

    if(is_array($user)) {
        $mail = $user['mail'];
        $name = $user['name'];
        $user = $user['user'];
    } else {
        $mail = $user;
    }

    // check first if a local image for the given user exists
    $userinfo = $auth->getUserData($user);
    if (is_array($userinfo)) {
      if (($userinfo['name']) && (!$title)) $title = hsc($userinfo['name']);
      $ns = $this->getConf('namespace');
      $formats = array('.png', '.jpg', '.gif');
      foreach ($formats as $format) {
        $user_img = mediaFN($ns.':'.$user.$format);
        $name_img = mediaFN($ns.':'.$name.$format);
        if(@file_exists($user_img)) {;
            $src = ml($ns.':'.$user.$format, array('w' => $size, 'h' => $size));
            break;
        } elseif(@file_exists($name_img)) {
            $src = ml($ns.':'.$name.$format, array('w' => $size, 'h' => $size));
        }
      }
      if (!$src) $mail = $userinfo['mail'];
    }
    
    if (!$src) {
      $seed = md5(utf8_strtolower($mail));
      
      if (function_exists('imagecreatetruecolor')) {
        // we take the monster ID as default
        $file = 'monsterid.php?seed='.$seed.'&size='.$size.'&.png';
          
      } else {
        // GDlib is not availble - resort to default images
        switch ($size) {
        case 20: case 40: case 80:
          $file = 'images/default_'.$size.'.png';
          break;
        default:
          $file = 'images/default_120.png';
        }
      }
      $default = ml(DOKU_URL.'/lib/plugins/avatar/'.$file, 'cache=recache', true, '&', true);
      
      // do not pass invalid or empty emails to gravatar site...
      if (mail_isvalid($mail) && ($size <= 80)){
        $src = ml('http://www.gravatar.com/avatar.php?'.
          'gravatar_id='.$seed.
          '&default='.urlencode($default).
          '&size='.$size.
          '&rating='.$this->getConf('rating').
          '&.jpg', 'cache=recache');
      
      // show only default image if invalid or empty email given
      } else {
        $src = $default;
      }
    }
    
    if (!$title) $title = obfuscate($mail);
    
    return $src;
  }
}
// vim:ts=4:sw=4:et:enc=utf-8:
