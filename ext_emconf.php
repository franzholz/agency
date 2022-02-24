<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Agency Registration',
    'description' => 'An improved variant of Kasper Skårhøj’s Front End User Admin extension.',
    'category' => 'plugin',
    'state' => 'stable',
    'uploadfolder' => 1,
    'clearcacheonload' => 1,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'version' => '0.11.0',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.1.99',
            'typo3' => '10.4.0-11.5.99',
            'div2007' => '1.14.3-0.0.0',
            'rdct' => '2.0.0-0.0.0',
            'typo3db_legacy' => '1.0.0-1.1.99',
        ],
        'conflicts' => [
            'sr_feuser_register' => '',
        ],
        'suggests' => [
            'felogin' => '',
            'rsaauth' => '',
            'saltedpasswords' => '',
            'static_info_tables' => '',
        ],
    ],
];

