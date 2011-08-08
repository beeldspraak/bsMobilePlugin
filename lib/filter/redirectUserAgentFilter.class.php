<?php
/**
 * Redirect request if it was issued by one of the configured user agents
 * params:
 *  - array userAgents
 *  - string redirectUrl (may contain a %s sprintf placeholder)
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
  public function execute($filterChain)
  {

  	// Execute this filter only once
    if ($this->isFirstCall())
    {
	    $context  = $this->getContext();
	    $request  = $context->getRequest();

	    foreach ($this->getStopParameters() as $stopParameter) {
	    	if ($request->hasParameter($stopParameter)) {
	    		$filterChain->execute();
	    		$content = $this->context->getResponse()->getContent();
	    		$content = self::addStopParameterToLinks($content, $stopParameter);
	    		$this->context->getResponse()->setContent($content);
	    		return;
	    	}
	    }

	    $redirectUrl = $this->getRedirectUrl();
	    $userAgent = strtolower($request->getHttpHeader('User-Agent'));
	    $userAgents = $this->getUserAgents();
	    foreach ($userAgents as $checkAgent) {
	    	if (false !== strpos($userAgent, $checkAgent)) {
		    	if (sfConfig::get('sf_logging_enabled'))
					{
					  $context->getLogger()->info("Mobile user agent '$checkAgent' detected. Redirecting to '$redirectUrl'");
					}
	    		$context->getController()->redirect($redirectUrl, 0, 302);
	    		throw new sfStopException();
	    	}
	    }
    }

    $filterChain->execute();

    $content = $this->context->getResponse()->getContent();
    $content = $this->addJavascriptRedirect($content, $userAgents, $redirectUrl);
    $this->context->getResponse()->setContent($content);

  }

  protected function addJavascriptRedirect($content, $userAgents, $redirectUrl)
  {
  	$script = '
  	<script type="text/javascript">
  		var userAgents = '.json_encode($userAgents).';
  		var userAgent = navigator.userAgent
  		if (userAgent) {
				for(var i=0; i<useragents .length; i++) {
					var regex = new RegExp(userAgents[i], "i");
					if (regex.exec(userAgent)) {
						document.location.href=decodeURIComponent("'.urlencode($redirectUrl).'");
					}
				}
  		}
  	</script>';

  	return str_replace('<head>', '</head><head>'.$script, $content);
  }

  /**
   * We want to preserve the stopParemetrs so we have to append it to all links
   *
   * @param unknown_type $stopParameter
   * @return unknown_type
   */
  public static function addStopParameterToLinks($content, $stopParameter)
  {
    //search and replace URLs
    $pattern = '/(<a .+href|action)="([^"]+)"/i';
    $replacement = '$1="$2?'.$stopParameter.'=1"';
    $content = preg_replace($pattern, $replacement, $content);

    //search and replace urls that already had an querystring parameter and thus now have two questionmarks in url
    $pattern = '/(<a.+href|action)="([^?]+)\?(.*)\?'.$stopParameter.'=1"/i';
    $replacement = '$1="$2?$3&'.$stopParameter.'=1"';
    $content = preg_replace($pattern, $replacement, $content);
    return $content;
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
}