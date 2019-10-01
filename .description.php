<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = array(
    'NAME'        => 'Слайдер фото',
    'DESCRIPTION' => 'Выводит слайдер фото с использованием highloadblock',
    'ICON'        => 'images/hl_list.gif',
    'CACHE_PATH'  => 'Y',
    'SORT'        => 10,
    'PATH'        => array(
        'ID'    => 'sv_component',
        'NAME'  => 'Svarok',
        'CHILD' => array(
            'ID'   => 'sv_content',
            'NAME' => 'Контент',
        ),
    ),
);
