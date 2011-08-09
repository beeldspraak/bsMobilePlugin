<?php
/**
 * Redirect request if it was issued by one of the configured user agents
 * params:
 * - array userAgents
 * - string redirectUrl (may contain a %s sprintf placeholder)
 *
 * Example filters.yml integration:
 *
 * <pre>
redirectUserAgent:
  class:  redirectUserAgentFilter
  enabled: on
  param:
    redirectUrl: http://mobile.yourdomain.com
    userAgents:
      - android
      - iphone
      - ipod
</pre>
 *
 */
class redirectUserAgentFilter extends sfFilter
{
  /**
   * Matched user agent for the request
   * @var mixed
   */
  protected $matchedUserAgent = null;
  

  public function execute($filterChain)
  {
    
    // Execute this filter only once
    if ( $this->isFirstCall() ) {
      $context = $this->getContext();
      $user = $context->getUser();
      $request = $context->getRequest();
      $routing = $context->getRouting();
      
      // check if a redirect stop is stored in the user session
      if ( self::hasStopForUser($user) ) {
        // proceed filter chain
        $filterChain->execute();
        return;
      }
      
      // check matching user agent
      $userAgent = $this->matchesUserAgentForRequest($request);

      if ($userAgent) {
        // check for stop parameters
        foreach ($this->getStopParameters() as $stopParameter) {
          if ( $request->hasParameter($stopParameter) ) {
            self::setStopForUser($user, $userAgent);
            // proceed filter chain
            $filterChain->execute();
            return;
          }
        }
        
        // check for stop routes
        foreach ($this->getStopRoutes() as $stopRoute) {
          if ( $routing->getCurrentRouteName() === $stopRoute ) {
            // proceed filter chain
            $filterChain->execute();
            return;
          }
        }
      
        // no stops found, redirect user
        $redirectUrl = $this->getRedirectUrl();
        if ( sfConfig::get('sf_logging_enabled') ) {
          $context->getLogger()->info("Mobile user agent '$userAgent' detected. Redirecting to '$redirectUrl'");
        }
        $context->getController()->redirect($redirectUrl, 0, 302);
        throw new sfStopException();
      }
    }
    
    $filterChain->execute();
    
    $content = $this->context->getResponse()->getContent();
    $content = $this->addJavascriptRedirect($content, $this->getUserAgents(), $this->getRedirectUrl());
    $this->context->getResponse()->setContent($content);
  
  }

  protected function addJavascriptRedirect($content, $userAgents, $redirectUrl)
  {
    $script = '
  	<script type="text/javascript">
  		var userAgents = ' . json_encode($userAgents) . ';
  		var userAgent = navigator.userAgent
  		if (userAgent) {
				for(var i=0; i<useragents .length; i++) {
					var regex = new RegExp(userAgents[i], "i");
					if (regex.exec(userAgent)) {
						document.location.href=decodeURIComponent("' . urlencode($redirectUrl) . '");
					}
				}
  		}
  	</script>';
    
    return str_replace('<head>', '</head><head>' . $script, $content);
  }

  public static function setStopForUser(sfUser $user, $userAgent)
  {
    $user->setAttribute('stop', $userAgent, 'bs_mobile');
  }

  public static function resetStopForUser(sfUser $user)
  {
    $user->getAttributeHolder()->remove('stop', null, 'bs_mobile');
  }

  public static function hasStopForUser(sfUser $user)
  {
    return $user->getAttribute('stop', false, 'bs_mobile') ? true : false;
  }

  public static function isMobileDeviceForUser(sfUser $user)
  {
    return self::hasStopForUser($user);
  }
  
  public static function getMobileDeviceForUser(sfUser $user)
  {
    return $user->getAttribute('stop', false, 'bs_mobile');
  }

  /**
   * Check if one of the configured user agents matches
   * 
   * @param sfRequest $request
   * @return mixed first matching user agent, FALSE if none matches
   */
  protected function matchesUserAgentForRequest(sfRequest $request)
  {
    if (is_null($this->matchedUserAgent))
    {
      $this->matchedUserAgent = false;
      $userAgent = strtolower($request->getHttpHeader('User-Agent'));
      $userAgents = $this->getUserAgents();
      foreach ($userAgents as $checkAgent) {
        if ( false !== strpos($userAgent, $checkAgent) ) {
          $this->matchedUserAgent = $checkAgent;
        }
      }
    }
    
    return $this->matchedUserAgent;
  }

  /**
   * Returns a list of user agents that should get redirected.
   *
   * Uses userAgents parameter configured in filters.yml but may be overwritten
   *
   * @return array
   */
  protected function getUserAgents()
  {
    return $this->getParameter('userAgents', array());
  }

  /**
   * Returns the url to which the matching user agents should get redirected.
   *
   * Uses redirectUrl parameter configured in filters.yml but may be overwritten
   *
   * @return string
   */
  protected function getRedirectUrl()
  {
    return $this->getParameter('redirectUrl');
  }

  /**
   * Returns a list of request parameter names that should stop/suppress redirection
   *
   * Uses stopParameters parameter configured in filters.yml but may be overwritten
   *
   * @return array
   */
  protected function getStopParameters()
  {
    return $this->getParameter('stopParameters', array());
  }

  /**
   * Returns a list of route names that should stop/suppress redirection
   *
   * Uses stopRoutes parameter configured in filters.yml but may be overwritten
   *
   * @return array
   */
  protected function getStopRoutes()
  {
    return $this->getParameter('stopRoutes', array());
  }
}