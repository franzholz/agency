<?php


$EM_CONF[$_EXTKEY] = array(
    'title' => 'Agency Registration',
    'description' => 'An improved variant of Kasper Skårhøj\'s Front End User Admin extension.',
    'category' => 'plugin',
    'shy' => 0,
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => 0,
    'uploadfolder' => 1,
    'createDirs' => '',
    'modify_tables' => 'fe_users',
    'clearcacheonload' => 1,
    'lockType' => '',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'version' => '0.6.1',
    'constraints' => array(
        'depends' => array(
            'php' => '5.5.0-7.99.99',
            'typo3' => '4.5.0-8.99.99',
            'div2007' => '1.9.1-0.0.0',
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
    'dependencies' => 'div2007',
    'conflicts' => 'sr_feuser_register',
);

