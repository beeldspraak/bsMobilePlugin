# bsMobilePlugin #

Detect the mobile handset by its user agent and redirect to the provided mobile site. This is the code from this blog post in an easy to install plugin: [Blog post](http://jnotes.jonasfischer.net/2009/11/symfony-filter-redirect-useragent/ "Symfony filter redirect useragent")

* _redirectUserAgentFilter_: redirect a request if it was issued by one of the configured user agents
* _redirectUserAgentDiemFilter_: additionally map Diem module-action combinations to the corresponding pages on the mobile site 

## Installation ##

Install from the project root.

* For a git project

        $ git submodule add bsgitexternal:beeldspraak/sf/bsMobilePlugin plugins/bsMobilePlugin

* For other projects, optionally add ".git" to ignore from the version control system

        $ git clone bsgitexternal:beeldspraak/sf/bsMobilePlugin plugins/bsMobilePlugin
        
* enable the plugin in _config/ProjectConfiguration.class.php_

## Configuration ##

### Simple example
    # apps/front/config/filter.yml
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
    # ...      
        
### (Diem) Forward specific content pages to the coresponding mobile pages
    # apps/front/config/filter.yml
    redirectUserAgent:
      class:  redirectUserAgentDiemFilter
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
      mapModules:
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
    # ...
        
## User ##
The user object is extended with some methods, see _lib/MobileUser.class.php_:

* _isMobileDevice()_: return if the user browsing the site is using a mobile device
* _getMobileDevice()_: return the user agent of the mobile device of the user
* _resetRedirectStop()_

Use it for example in your template to display a button to go back to the mobile site:

    <!-- apps/front/main/templates/_header.php -->
    <?php if ($sf_user->isMobileDevice()): ?>
    <div id="mobile_site">
      <p><a href="<?php echo url_for('@mobile_site') ?>">Mobile site</a></p>
    </div>
    <?php endif; ?>
    <!-- ... -->

    // ...
    public function executeMobileSite(sfWebrequest $request)
    {
      // remove from session
      $this->getUser()->resetRedirectStop();
          
      $this->redirect('http://mobile.yourdomain.com');
    }
    // ...