<?php
/**
 * MobileUser.class.php
 *
 * Extends the user class to store and access if the user is browsing the site with a mobile device 
 * and what the user agent is of the mobile device
 *
 * @package bsMobilePlugin
 */
class MobileUser
{
  /**
   * Event listener to connect this class to the user class
   * 
   * @param sfEvent $event
   */
  static public function methodNotFound(sfEvent $event)
  {
    if (method_exists('MobileUser', $event['method']))
    {
      $event->setReturnValue(call_user_func_array(
        array('MobileUser', $event['method']),
        array_merge(array($event->getSubject()), $event['arguments'])
      ));
 
      return true;
    }
  }
  
  /**
   * Set a redirect stop and specify the user agent of the device browsing the site
   *  
   * @param sfUser $user
   * @param string $userAgent
   */
  public static function setRedirectStop(sfUser $user, $userAgent)
  {
    $user->setAttribute('redirect_stop', $userAgent, 'bs_mobile');
  }

  /**
   * Reset a redirect stop
   * 
   * @param sfUset $user
   */
  public static function resetRedirectStop(sfUser $user)
  {
    $user->getAttributeHolder()->remove('redirect_stop', null, 'bs_mobile');
  }

  /**
   * Return if the user browsing the site is using a mobile device and choose to stop a redirect
   * 
   * @param sfUser $user
   * @return boolean
   */
  public static function hasRedirectStop(sfUser $user)
  {
    return $user->getAttribute('redirect_stop', false, 'bs_mobile') ? true : false;
  }

  /**
   * Return if the user browsing the site is using a mobile device
   * 
   * @param sfUser $user
   * @return boolean
   */
  public static function isMobileDevice(sfUser $user)
  {
    return self::hasRedirectStop($user);
  }
  
  /**
   * Return the user agent of the mobile device of the user
   *  
   * @param sfUser $user
   * @return string
   */
  public static function getMobileDevice(sfUser $user)
  {
    return $user->getAttribute('redirect_stop', false, 'bs_mobile');
  }
}