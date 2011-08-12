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
    # map the current route for the given modules and add the url to the redirectUrl, replace parameters
    mapModules:
      project:
        url:    '#work/show/:id'
        params: { id: ':id' } # get the record 'id' to replace ':id'
      news_post:
        url:    '#recent/show/:id'
        params: { id: ':id' }
      blog_post:
        url:    '#recent/show/:id'
        params: { id: ':id' }        
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
    
    // check if a matching pageRoute is found that has a page and a record
    if ( $pageRoute && $pageRoute->getPage() && $pageRoute->getPage()->hasRecord() ) {
      // find a mapped mobile route
      $module = sfInflector::underscore($pageRoute->getPage()->get('module'));
      $mapModules = $this->getMapModules();
      if (isset($mapModules[$module])) {
        // prepare path
        $mappedModule = $mapModules[$module];
        $url = isset($mappedModule['url']) ? $mappedModule['url'] : false;
        $record = $pageRoute->getPage()->getRecord();
        if ($url && isset($mappedModule['params'])) {
          // replace parameters in path
          $replacePairs = array();
          $replacePairsFailure = false;
          foreach ($mappedModule['params'] as $property => $parameter) {
            if (!$record->getTable()->hasField($property)) {
              $replacePairsFailure = true;
              break;
            }
            $replacePairs[$parameter] = $record->get($property);
          }
          if (!$replacePairsFailure) {
            $result = strtr($url, $replacePairs);
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
}