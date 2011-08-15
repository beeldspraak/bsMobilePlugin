<?php

/**
 * bsMobilePlugin configuration.
 * 
 * @package     bsMobilePlugin
 * @subpackage  config
 * @author      Your name here
 * @version     SVN: $Id: PluginConfiguration.class.php 17207 2009-04-10 15:36:26Z Kris.Wallsmith $
 */
class bsMobilePluginConfiguration extends sfPluginConfiguration
{
  const VERSION = '1.0.0';

  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    $this->dispatcher->connect('user.method_not_found', array('MobileUser', 'methodNotFound'));
  }
}
