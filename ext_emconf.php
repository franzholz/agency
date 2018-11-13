<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Agency Registration',
    'description' => 'An improved variant of Kasper Sk�rh�j\'s Front End User Admin extension.',
    'category' => 'plugin',
    'state' => 'stable',
    'uploadfolder' => 1,
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'version' => '0.8.1',
    'constraints' => array(
        'depends' => array(
            'php' => '5.5.0-7.99.99',
            'typo3' => '6.2.0-8.99.99',
            'div2007' => '1.10.8-0.0.0',
        ),
        'conflicts' => array(
            'sr_feuser_register' => '',
        ),
        'suggests' => array(
            'felogin' => '',
            'rsaauth' => '',
            'saltedpasswords' => '',
            'static_info_tables' => '',
        ),
    ),
);

