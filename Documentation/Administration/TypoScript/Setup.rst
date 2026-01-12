:navigation-title: Typoscript Setup
..  _typoscript-setup:

================
TypoScript Setup
================

Please note that the following properties refer to **TS template setup**, not to TS template constants!
When installing this extension, you should **NOT** use the following setting:
:typoscript:`config.disableAllHeaderCode = 1`

This setting will prevent the inclusion of Javascript essential to the correct behavior of the profile editing features.


Properties
==========

..  contents::
    :local:


..  _extra-labels:

extraLabels
-----------

..  confval:: extraLabels
    :name: extra-labels
    :type: string

    Comma-separated list of additional labels to use in the HTML template.

    See section Labels and localisation about adding extra  labels.

..  _setup-template-file:

templateFile
------------

..  confval:: templateFile
    :name: templateFile
    :type: string

    Same as :typoscript:`file.templateFile` from Constants. This is needed under Setup for language specific template files only.


..  _where-static-countries:

where.static_countries
-----------------------

..  confval:: extraLabels
    :name: where-static_countries
    :type: string

    Set a SQL WHERE condition to follow for the selected country in the :php:`static_countries` table of the :php:`static_info_tables` extension. 
    Maybe you want to see only users from EU countries on your site.

    See section Labels and localisation about adding extra  labels.

    Example 1:
    plugin.tx_agency { 
     where.static_countries = cn_eu_member = 1
    }
    
    Example 2:
    ..  code-block:: php
        :caption:  plugin.tx_agency.where.static_countries
    
        plugin.tx_agency { 
           where.static_countries = static_countries.cn_iso_3 IN ('AFN','AGO','AIA','ALL','ANT','ARE','ARM','ASM','ATA','ATF','ATG','ASM','AUT','AZE',
               'BDI','BEN','BFA','BGR','BHR','BIH','BLR','BMU','BRB','BRN','BTN','BVT','BWA','CAF','CCK','CIV','CMR','COK','COL',
               'COM','CPV','CRI','CSG','CUB','CXR','CYM','CYP','CZE','DEU','DJI','DNK','DZA','ECU','EGY','ERI','ESH','ESP','EST',
               'ETH','FIN','FJI','FLK','FRO','FSM','GAB','GEO','GHA','GIB','GIN','GLP','GMB','GNB','GNQ','GRD','GRL','GUF','GUY',
               'HMD','HRV','HUN','IRN','IRQ','ISL','ISR','ITA','JAM','JOR','KAZ','KEN','KGZ','KIR','KNA','KWT','LBR','LBN','LBY',
                'LCA','LIE','LSO','LTU','LUX','LVA','MAR','MCO','MDA','MDG','MHL','MKD','MLI','MLT','MNP','MOZ','MRT','MSR','MTQ',
                'MUS','MWI','MYT','NAM','NCL','NER','NFK','NGA','NIC','NLD','NOR','NPL','NRU','OMN','PAK','PCN','PLW','PNG','POL',
                'PYF','QAT','REU','ROU','RUS','RWA','SAU','SDN','SEN','SGS','SHN','SJM','SLE','SLB','SLV','SMR','SOM','SPM','STP',
                'SRB','SUR','SVK','SVN','SWE','SWZ','SYC','SYR','TCA','TCD','TGO','TJS','TKL','TKM','TLS','TON','TTO','TUN','TUR',
                'TZA','UGA','UKR','UMI','URY','UZB','VAT','VCT','VGB','VIR','VNM','VUT','WLF','WSM','XAF','YEM','ZAF','ZAR','ZMB','ZWE')
        }


..  _eval-func:

evalFunc
--------

..  confval:: evalFunc
    :name: eval-func
    :type: function-name

    additional evaluation function for the records, e.g. :php:`user_myfunction`or :php:`user_myclass->main`


..  _user-func-update-array:

userFunc_updateArray
--------------------

..  confval:: evalFunc
    :name: eval-func
    :type: function-name

    User function to update the records	


..  _display-required:

displayRequired
---------------

..  confval:: displayRequired
    :name: display-required
    :type: cObject

    Use this cOject for the display of required fields. By default the required fields are printed with an asterisk (*).

    **Example:**
    ..  code-block:: typoscript
        :caption:  displayRequired = COA

        displayRequired = COA
        displayRequired {
           10 = TEXT
           10.value = You must fill this in!
           10.wrap = <b>Attention:</b> | *
        }


..  _captcha:

captcha
-------

..  confval:: captcha
    :name: captcha
    :type: array of array

    Set the used captcha extensions. If you want to support more captcha extensions then you must add them here.

    extensionKey ... extension key
    evalRule ... string to be used in evalValues.captcha_response

    **Example:**
    ..  code-block:: typoscript
        :caption:  captcha

    captcha {
    	10 {
    		extensionKey = sr_freecap
    		evalRule = freecap
    	}
    	20 {
    		extensionKey = captcha
    		evalRule = captcha
    	}
    }






