<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var $arParams */
/** @var $arResult */
?>

<?php if (!empty($arResult['ELEMENTS'])) { ?>
    <div class="main-board__right">
        <?php foreach ($arResult['ELEMENTS'] as $arItem) { ?>
            <div class="main-board__promo">
                <a href="<?= $arItem['UF_LINK']; ?>">
                    <img src="<?= \CFile::GetPath($arItem['UF_IMAGE']); ?>" alt="<?= $arItem['UF_NAME']; ?>">
                </a>
            </div>
        <?php } ?>
    </div>
<?php }
