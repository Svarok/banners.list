<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var $arParams */
/** @var $arResult */
?>

<?php if (!empty($arResult['ELEMENTS'])) { ?>
    <div class="main-board__left">
        <div class="main-board__slider js-main-slider">
            <?php foreach ($arResult['ELEMENTS'] as $arItem) { ?>
                <div class="main-board__slider-item">
                    <a href="<?= $arItem['UF_LINK']; ?>">
                        <img src="<?= \CFile::GetPath($arItem['UF_IMAGE']); ?>" alt="<?= $arItem['UF_NAME']; ?>">
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>
<?php }
