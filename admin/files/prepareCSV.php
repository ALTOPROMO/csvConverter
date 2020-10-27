<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin.php");

global $DB;

$file=false;

if(isset($_POST['csvtype'])) {

    $lang=$_POST['lang'];
    CModule::IncludeModule('leadercsvprepare');

    $processor = new LeaderCSVPrepare($lang);

    $file=$_FILES['csvfile']['tmp_name'];
    $type=$_POST['csvtype'];


    $file=$processor->process($file,$type);
}
?>
<h1>Подготовка импорта CSV</h1>
<?php

if($file)
{
	echo('<p>Файл загружен по адресу <span style="color:red">'.$file.'</span></p>');
}

?>
<form method="post" action="/bitrix/admin/prepareCSV.php" enctype="multipart/form-data">
    <fieldset  style="width:400px;padding:10px;margin-bottom:30px;">
        <legend>Язык</legend>
        <div>
            <label for="lang_ru">Русский</label><input type="radio" name="lang" id="lang_ru" value="ru" checked/>
            <label for="lang_kz">Казахский</label><input type="radio" name="lang" id="lang_kz" value="kz"/>
        </div>

    </fieldset>
    <fieldset style="width:400px;padding:10px;margin-bottom:30px;">
        <legend>Тип загрузки</legend>
        <div>
            <label for="csvtype_1">Акции</label><input type="radio" name="csvtype" id="csvtype_1" value="action" checked />
        </div>
        <div>
            <label for="csvtype_2">Новинки</label><input type="radio" name="csvtype" id="csvtype_2" value="newtag"/>
        </div>
        <div>
            <label for="csvtype_3">Остатки</label><input type="radio" name="csvtype" id="csvtype_3" value="leftovers"/>
        </div>
        <div>
            <label for="csvtype_4">Полный файл</label><input type="radio" name="csvtype" id="csvtype_4" value="full"/>
        </div>
       <?php /* ?>
        <div>
            <label for="csvtype_5">Загрузить бренды</label><input type="radio" name="csvtype" id="csvtype_5" value="brand"/>
        </div>
 <? /**/?>
        <div>
            <label for="csvtype_6">Загрузить ЭБЖУ</label><input type="radio" name="csvtype" id="csvtype_6" value="props"/>
        </div>
    </fieldset>

    <label for="csvfile">Файл</label>
    <input type="file" id="csvfile" name="csvfile"/>
    <button type="submit">Обработать</button>
</form>
<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>