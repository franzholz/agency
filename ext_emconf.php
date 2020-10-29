<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Agency Registration',
    'description' => 'An improved variant of Kasper Skårhøj\'s Front End User Admin extension.',
    'category' => 'plugin',
    'state' => 'stable',
    'uploadfolder' => 1,
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'version' => '0.9.1',
    'constraints' => array(
        'depends' => array(
            'php' => '5.5.0-7.99.99',
            'typo3' => '7.6.0-9.5.99',
            'div2007' => '1.10.28-0.0.0',
        ),
        'conflicts' => array(
            'sr_feuser_register' => '',
        ),
        'suggests' => array(
            'felogin' => '',
            'rdct' => '',
            'rsaauth' => '',
            'saltedpasswords' => '',
            'static_info_tables' => '',
            'typo3db_legacy' => '1.0.0-1.1.99',
        ),
    ),
);

