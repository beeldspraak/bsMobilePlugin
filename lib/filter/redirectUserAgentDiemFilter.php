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
      - android, mobile # android mobile, seperate multiple words in the user agent by a comma
      - android # android tablet
      - iphone
      - ipod
      - ipad
      - blackberry      
    # prevent a mobile device with the configured userAgents to be redirected
    stopParameters:
      - no_mobile
    stopRoutes:
      - project_api
      - recent_api
    # map the current route for the given module and actions and add the url to the redirectUrl, replace parameters
    mapModuleActions:
      project/show:
        url:    '#work/show/:id'
        params: { id: ':id' } # get the record 'id' to replace ':id'
      news_post/show:
        url:    '#recent/show/:id'
        params: { id: ':id' }
      blog_post/show:
        url:    '#recent/show/:id'
        params: { id: ':id' }
      main/contact: { url: '#contact/index' }    
</pre>
 *
 */
class redirectUserAgentDiemFilter extends redirectUserAgentFilter
{

  /**
   * Get the url path for the redirectUrl
   *
   * @return string
   */
  protected function getRedirectUrlPath()
  {
    $result = '';
    $context = $this->getContext();
    $request = $context->getRequest();
    $routing = $context->getRouting();
    $slug = $request->getParameter('slug');
    $pageRoute = $context->getServiceContainer()->getService('page_routing')->find($slug);

    // check if a matching pageRoute is found that has a page
    if ( $pageRoute && $pageRoute->getPage() ) {
      
      // find a mapped mobile route
      $moduleAction = $pageRoute->getPage()->getModuleAction();
      $mapModuleActions = $this->getMapModuleActions();
      
      if (isset($mapModuleActions[$moduleAction])) {
        // prepare path
        $mappedModule = $mapModuleActions[$moduleAction];
        $url = isset($mappedModule['url']) ? $mappedModule['url'] : false;
        
        // optionally process parameters
        $parametersFailure = false;
        $replacePairs = array();
        if ($pageRoute->getPage()->hasRecord() && $url && isset($mappedModule['params'])) {
          $record = $pageRoute->getPage()->getRecord();

          // replace parameters in path
          foreach ($mappedModule['params'] as $property => $parameter) {
            if (!$record->getTable()->hasField($property)) {
              $parametersFailure = true;
              break;
            }
            $replacePairs[$parameter] = $record->get($property);
          }
        }
        
        // replace parameters or use a static route
        if (!$parametersFailure) {
          if (count($replacePairs)) {
            $result = strtr($url, $replacePairs);
          } else {
            $result = $url;
          }
        }
      }
    }
    
    return $result;
  }

  /**
   * Override method
   * @see filter/redirectUserAgentFilter#getRedirectUrl()
   *
   * - add the url path for the redirectUrl
   *
   * @return string | null
   */
  protected function getRedirectUrl()
  {
    return parent::getRedirectUrl().$this->getRedirectUrlPath();
  }
  
  /**
   * Returns a list of module-action combinations and the configuration to map them to the redirectUrl
   *
   * Uses mapModuleActions parameter configured in filters.yml but may be overwritten
   *
   * @return array
   */
  protected function getMapModuleActions()
  {
    return $this->getParameter('mapModuleActions', array());
  }
}