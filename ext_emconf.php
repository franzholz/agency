<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Agency Registration',
    'description' => 'An improved variant of Kasper Skårhøj’s Front End User Admin extension.',
    'category' => 'plugin',
    'state' => 'stable',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'version' => '0.14.2',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'div2007' => '2.2.0-0.0.0',
            'rdct' => '3.0.0-0.0.0',
            'typo3db_legacy' => '1.3.0-1.3.99',
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
