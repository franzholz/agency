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
    'version' => '0.12.1',
    'constraints' => [
        'depends' => [
            'php' => '8.0.0-8.4.99',
            'typo3' => '11.5.0-12.4.99',
            'div2007' => '1.17.0-0.0.0',
            'rdct' => '2.0.0-0.0.0',
            'typo3db_legacy' => '1.0.0-1.2.99',
        ],
        'conflicts' => [
            'sr_feuser_register' => '',
        ],
        'suggests' => [
            'felogin' => '',
            'static_info_tables' => '',
        ],
    ],
];

