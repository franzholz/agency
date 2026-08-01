<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Agency Registration',
    'description' => 'An improved variant of Kasper Skårhøj’s Front End User Admin extension.',
    'category' => 'plugin',
    'state' => 'stable',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'version' => '1.1.2',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'div2007' => '2.4.7-0.0.0',
            'rdct' => '3.2.0-0.0.0'
        ],
        'conflicts' => [
            'sr_feuser_register' => '',
        ],
        'suggests' => [
            'felogin' => '',
            'static_info_tables' => ''
        ],
    ],
];
