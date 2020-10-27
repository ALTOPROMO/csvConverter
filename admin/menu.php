<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$aMenu = array(
    array(
        'parent_menu' => 'global_menu_store',
        'sort' => 400,
        'text' => "Подготовка CSV",
        'title' => "",
        'url' => '',
        'items_id' => 'altekrom_menu',
        'url'=>'/bitrix/admin/prepareCSV.php'
    )
);

return $aMenu;

?>