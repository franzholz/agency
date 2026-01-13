:navigation-title: Short Urls
..  _short-urls:

==========
Short Urls
==========

Reducing the length of URL's
=============================

You may find that the URL's sent in emails to the front end user are too long and may be broken when using plain text emails.

Using plain text emails and notification_email_urlmode
-------------------------------------------------------

If you are using plain text emails (enableHTMLMail is set to 0):

*  In your TS template setup, set 
   :typoscript:`config.notification_email_urlmode = 76` or 
   :typoscript:`config.notification_email_urlmode  = all` . 
   See TSRef for information about this CONFIG setup property.

Using the short URL feature - RealURL
-------------------------------------

This approach is compatible with both HTML and plain text emails. Simply enable the feature by setting the TS constant useShortUrls = 1 in your TS template. You should also review the default value of TS constant shortUrlLife and set it to a value that fits your needs.
If you are using the RealURL extension, you should add something like the following to your RealURL configuration in the $TYPO3_CONF_VARS['EXTCONF']['realurl'] variable of localconf.php:

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    'postVarSets' => array(
          '_DEFAULT' => array(
              'user' => array(
                  array(
                      'GETvar' => 'agency[regHash]'
                  )
              )
          )
      ),

