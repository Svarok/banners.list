<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arSort = [
    'UF_NAME' => Loc::getMessage('SORT_FIELD_NAME'),
    'UF_SORT' => Loc::getMessage('SORT_FIELD_SORT'),
];

$arOrder = [
    'ASC'  => Loc::getMessage('SORT_ASC'),
    'DESC' => Loc::getMessage('SORT_DESC'),
];

$arComponentParameters = [
    'GROUPS'     => [],
    'PARAMETERS' => [
        'BLOCK_ID'   => [
            'PARENT'   => 'BASE',
            'NAME'     => Loc::getMessage('BLOCK_ID'),
            'TYPE'     => 'STRING',
            'DEFAULT'  => '',
            'MULTIPLE' => 'N',
            'REFRESH'  => 'N',
        ],
        'LIMIT'      => [
            'PARENT'   => 'BASE',
            'NAME'     => Loc::getMessage('LIMIT'),
            'TYPE'     => 'STRING',
            'DEFAULT'  => '10',
            'MULTIPLE' => 'N',
            'REFRESH'  => 'N',
        ],
        'SORT_FIELD' => [
            'PARENT'   => 'BASE',
            'NAME'     => Loc::getMessage('SORT_FIELD'),
            'TYPE'     => 'LIST',
            'VALUES'   => $arSort,
            'DEFAULT'  => '',
            'MULTIPLE' => 'N',
            'REFRESH'  => 'N',
        ],
        'SORT_ORDER' => [
            'PARENT'   => 'BASE',
            'NAME'     => Loc::getMessage('SORT_ORDER'),
            'TYPE'     => 'LIST',
            'VALUES'   => $arOrder,
            'DEFAULT'  => '',
            'MULTIPLE' => 'N',
            'REFRESH'  => 'N',
        ],
    ],
];
