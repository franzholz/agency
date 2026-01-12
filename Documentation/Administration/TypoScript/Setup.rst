:navigation-title: Typoscript Setup
..  _typoscript-setup:

================
TypoScript Setup
================



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


..  _extra-labels:

where.static_countries
-----------------------

..  confval:: extraLabels
    :name: extra-labels
    :type: string

    Set a SQL WHERE condition to follow for the selected country in the :php:`static_countries` table of the :php:`static_info_tables` extension. 
    Maybe you want to see only users from EU countries on your site.

    See section Labels and localisation about adding extra  labels.

    Example 1:
    plugin.tx_agency { 
     where.static_countries = cn_eu_member = 1
    }
    
    Example 2:
    plugin.tx_agency { 
      where.static_countries = static_countries.cn_iso_3 IN ('AFN','AGO','AIA','ALL','ANT','ARE','ARM','ASM','ATA','ATF','ATG','ASM','AUT','AZE','BDI','BEN','BFA','BGR','BHR','BIH',
'BLR','BMU','BRB','BRN','BTN','BVT','BWA','CAF','CCK','CIV','CMR','COK','COL','COM','CPV','CRI','CSG','CUB','CXR',
'CYM','CYP','CZE','DEU','DJI','DNK','DZA','ECU','EGY','ERI','ESH','ESP','EST','ETH','FIN','FJI','FLK','FRO','FSM',
'GAB','GEO','GHA','GIB','GIN','GLP','GMB','GNB','GNQ','GRD','GRL','GUF','GUY','HMD','HRV','HUN','IRN','IRQ','ISL',
'ISR','ITA','JAM','JOR','KAZ','KEN','KGZ','KIR','KNA','KWT','LBR','LBN','LBY','LCA','LIE','LSO','LTU','LUX','LVA',
'MAR','MCO','MDA','MDG','MHL','MKD','MLI','MLT','MNP','MOZ','MRT','MSR','MTQ','MUS','MWI','MYT','NAM','NCL','NER',
'NFK','NGA','NIC','NLD','NOR','NPL','NRU','OMN','PAK','PCN','PLW','PNG','POL','PYF','QAT','REU','ROU','RUS','RWA',
'SAU','SDN','SEN','SGS','SHN','SJM','SLE','SLB','SLV','SMR','SOM','SPM','STP','SRB','SUR','SVK','SVN','SWE','SWZ',
'SYC','SYR','TCA','TCD','TGO','TJS','TKL','TKM','TLS','TON','TTO','TUN','TUR','TZA','UGA','UKR','UMI','URY','UZB',
'VAT','VCT','VGB','VIR','VNM','VUT','WLF','WSM','XAF','YEM','ZAF','ZAR','ZMB','ZWE')
    }


