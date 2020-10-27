<?php

class LeaderCSVPrepare
{
    private $BRAND_RU = 30;   //IBLOCK_ID Брендов для русской версии
    private $BRAND_KZ = 61;   //IBLOCK_ID Брендов для казахской версии
    private $CATALOG_RU = 26; //IBLOCK_ID каталог для русской версии
    private $CATALOG_KZ = 57; //IBLOCK_ID каталог для казахской версии

    private $BRAND_ID;
    private $CATALOG_ID;
    private $lang;

    private $idCSV;
    private $idXML;
    private $idList;
    private $regionList = Array(// Код региона => id типа цен
        106 => '3', //Атырау евр
        121 => '2', //Атырау аз
        281 => '1', //Актау
        401 => '5', //Уральск
        505 => '4', //Павлодар
    );

    private $timerList;

    private $brandList;

    private $propListFull = Array(
        'ru' => Array(
            'HIT' => 'IP_PROP345',
            'BRAND' => 'IP_PROP347',
            'ENERGY' => 'IP_PROP1269',
            'PROTEIN' => 'IP_PROP1270',
            'FAT' => 'IP_PROP1271',
            'CARBON' => 'IP_PROP1272',
        ),
        'kz' => Array(
            'HIT' => 'IP_PROP970',
            'BRAND' => 'IP_PROP972',
            'ENERGY' => 'IP_PROP1273',
            'PROTEIN' => 'IP_PROP1274',
            'FAT' => 'IP_PROP1275',
            'CARBON' => 'IP_PROP1276',
        ),
    );

    private $propList;

    public $warnings = Array();

    public function __construct($lang = 'RU')// $region [RU,KZ]
    {

        $this->lang = strtolower($lang);
        if ($this->lang == 'kz') {
            $this->CATALOG_ID = $this->CATALOG_KZ;
            $this->BRAND_ID = $this->BRAND_KZ;
            $this->propList = $this->propListFull[$this->lang];
        } else {
            $this->lang = 'ru';
            $this->CATALOG_ID = $this->CATALOG_RU;
            $this->BRAND_ID = $this->BRAND_RU;
            $this->propList = $this->propListFull[$this->lang];
        }

        $rawidCSV = $this->getIDCSV();

        $idStartCSV = $this->processCSV(
            $rawidCSV,
            Array(
                'BX_ID' => 0,
                'ID_GSTORE' => 1,
                'XML_ID' => 2
            ));

        $this->idCSV = Array();
        $this->idList = Array();

        foreach ($idStartCSV as $row) {
            $this->idCSV[$row['ID_GSTORE']] = $row;
            $this->idXML[$row['BX_ID']] = $row;
            $this->idList[] = $row['BX_ID'];
        }
    }

    private function starttime($name)
    {
        $this->timerList[$name]['START'] = time();
    }

    private function endtime($name)
    {
        $this->timerList[$name]['END'] = time();
    }

    private function printtime($name = false)
    {
        if ($name) {
            $item = $this->timerList[$name];
            echo('time ' . $name . '[' . ($item['END'] - $item['START']) . ']<br>');
        } else {
            foreach ($this->timerList as $timerName => $item) {
                echo('time ' . $timerName . '[' . ($item['END'] - $item['START']) . ']<br>');
            }
        }
    }

    private function getPriceCollection()
    {
        $query = \Bitrix\Catalog\PriceTable::getList(
            Array(
                'select' => array('ID', 'PRICE', 'PRODUCT_ID', 'CATALOG_GROUP_ID'),
                'filter' => array('PRODUCT_ID' => $this->idList)
            )
        );

        $prices = Array();
        while ($item = $query->fetch()) {
            $bx_id = $item['PRODUCT_ID'];
            $code = 'CATALOG_PRICE_' . $item['CATALOG_GROUP_ID'];
            $price = $item['PRICE'];

            $prices[$bx_id]['ID'] = $bx_id;
            $prices[$bx_id][$code] = $price;

        }
        $priceCollection = Array();
        foreach ($prices as $bx_id => $item) {
            $priceCollection[$bx_id] = Array();

            foreach ($this->regionList as $regionCode => $regionPriceID) {
                $priceCollection[$bx_id][$regionCode] = $item['CATALOG_PRICE_' . $regionPriceID];
            }

        }
        return $priceCollection;
    }

    private function getDefaultPrice($priceRow)
    {
        $defaultPrice = $priceRow[281];// Актау - регион по умолчанию
        if ($defaultPrice == '') {
            foreach ($priceRow as $price) {
                if ($price != '') {
                    $defaultPrice = $price;
                    break;
                }
            }
        }
        return $defaultPrice;
    }


    public function process($filename, $type)
    {
        $this->warnings = Array();
        switch ($type) {
            case('action'): {
                $text = $this->createDiscount($filename);
            }
                break;
            case('newtag'): {
                $text = $this->createNewBage($filename);
            }
                break;
            case('leftovers'): {
                $text = $this->createLeftovers($filename);
            }
                break;
            case('full'): {
                $text = $this->createFull($filename);
            }
                break;
            case('brand'): {
                $text = $this->createBrandConnect($filename);
            }
                break;
            case('props'): {
                $text = $this->createProps($filename);
            }
                break;
            default: {
                $text = '';
            }
        }

        if (count($this->warnings) > 0) {
            echo(implode('<br/>', $this->warnings));
        }
        return $this->saveCSV($text, $type);

    }

    private function saveCSV($text, $type)
    {
        $filepath = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
        switch ($type) {
            case('action'): {
                $filename = '/bitrix/catalog_export/new_action.csv';
            }
                break;
            case('newtag'): {
                $filename = '/bitrix/catalog_export/new_newtag.csv';

            }
                break;
            case('leftovers'): {
                $filename = '/bitrix/catalog_export/new_leftovers.csv';

            }
                break;
            case('full'): {
                $filename = '/bitrix/catalog_export/new_full.csv';

            }
                break;
            case('brand'): {
                $filename = '/bitrix/catalog_export/new_brand.csv';

            }
                break;
            case('props'): {
                $filename = '/bitrix/catalog_export/new_props.csv';

            }
                break;
            default: {
                return false;
            }
        }

        file_put_contents($filepath . $filename, $text);
        return $filename;
    }


    private function getIDCSV()
    {
        $query = CIBlockElement::getList(
            Array(),
            Array('IBLOCK_ID' => $this->CATALOG_ID),
            false,
            false,
            Array('ID', 'XML_ID', 'IBLOCK_ID', 'PROPERTY_ATT_ID_GESTORE')
        );

        $csv = Array();

        while ($item = $query->fetch()) {
            $csv[] = [$item['ID'], $item['PROPERTY_ATT_ID_GESTORE_VALUE'], $item['XML_ID']];
        }

        return $csv;
    }


    private function getCSV($filename, $haveHeader = true)
    {
        $csv = Array();
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 0, ";")) !== FALSE) {
                $csv[] = $row;
            }
            fclose($handle);
        }

        if ($haveHeader) {
            array_shift($csv);
        }
        return $csv;
    }

    private function processCSV($csv, $columns)
    {
        $result = Array();
        foreach ($csv as $row) {
            $prow = Array();
            foreach ($columns as $name => $n) {
                $prow[$name] = $row[$n];
            }
            $result[] = $prow;
        }
        return $result;
    }

    private function glue($dataCSV, $musthave = true)
    {
        foreach ($dataCSV as &$row) {
            $id_gstore = $row['ID_GSTORE'];
            if (!isset($this->idCSV[$id_gstore])) {
                if ($musthave) {
                    $this->warnings[] = "$id_gstore не загружен\r\n";
                } else {
                    $row['BX_ID'] = '';
                }
                continue;
            }
            $row['BX_ID'] = $this->idCSV[$id_gstore]['BX_ID'];
        }
        return $dataCSV;
    }

    private function addRow($row)
    {
        return implode(';', $row) . "\r\n";
    }

    private function getCSVTEXT($array, $field_list, $musthave = true)
    {
        $csv = str_replace('BX_ID', 'IE_XML_ID', $this->addRow($field_list));
        foreach ($array as $row) {
            $arRow = Array();

            if (!isset($row['BX_ID']) || $row['BX_ID'] == '') {

                if ($musthave) {
                    continue;
                } else {
                    $row['BX_ID'] = '';
                }
            } else {
                $row['BX_ID'] = $this->idXML[$row['BX_ID']]['XML_ID'];
            }
            foreach ($field_list as $field) {
                $arRow[] = $row[$field];
            }
            $csv .= $this->addRow($arRow);
        }

        return $csv;
    }

    private function fillOuputArray($outputArray)
    {
        foreach ($this->regionList as $regionCode => $regionID) {
            $outputArray[] = 'CV_CURRENCY_' . $regionID;
            $outputArray[] = 'CV_PRICE_' . $regionID;
        }

        return $outputArray;
    }

    private function timeFormat($date)
    {
        $arDate = explode('.', $date);
        if (strlen($arDate[2]) != 4) {
            $arDate[2] = "20{$arDate[2]}";
        }
        return implode('.', $arDate);
    }

    private function pushPrices($body)
    {
        $priceCollection = $this->fillPriceCollection($body);

        foreach ($body as &$row) {
            if (!$row['BX_ID']) {
                $bx_id = "_{$row['ID_GSTORE']}";
            } else {
                $bx_id = $row['BX_ID'];
            }

            foreach ($priceCollection[$bx_id] as $regionCode => $price) {
                $regionPriceID = $this->regionList[$regionCode];
                $row['CV_CURRENCY_' . $regionPriceID] = 'KZT';
                $row['CV_PRICE_' . $regionPriceID] = $price;
            }
        }


        return $body;
    }


    private function createDiscount($filename)
    {
        $csv = $this->getCSV($filename);
        $action = $this->processCSV(
            $csv,
            Array(
                'ID_GSTORE' => 0,
                'REGIONE' => 1,
                'DISC_TIME_FROM' => 2,
                'DISC_TIME_TO' => 3,
                'PRICE' => 4,
                'DISC_VALUE_PERCENT' => 5,
                'DISC_VALUE' => 6,
            )
        );
        foreach ($action as &$row) {
            $row['DISC_VALUE_PERCENT'] = abs($row['DISC_VALUE_PERCENT']) . '%';
            $row['DISC_VALUE'] = abs($row['DISC_VALUE']);

            $row['DISC_TIME_FROM'] = $this->timeFormat($row['DISC_TIME_FROM']);
            $row['DISC_TIME_TO'] = $this->timeFormat($row['DISC_TIME_TO']);
        }
        $actionUpdated = $this->glue($action);

        $actionUpdated = $this->pushPrices($actionUpdated);
        $outputArray = Array(
            'BX_ID',
            'REGIONE',
            'DISC_TIME_FROM',
            'DISC_TIME_TO',
            'DISC_VALUE_PERCENT',
            'DISC_VALUE',
        );
        $outputArray = $this->fillOuputArray($outputArray);
        $result = $this->getCSVTEXT(
            $actionUpdated,
            $outputArray
        );

        return $result;
    }

    private function dump($data)
    {
        echo('<pre>');
        print_r($data);
        echo('</pre>');
    }

    private function fillPriceCollection($body)
    {
        $priceCollection = $this->getPriceCollection();

        foreach ($body as $row) {
            if (!$row['BX_ID']) {
                $bx_id = "_{$row['ID_GSTORE']}";
                $priceCollection[$bx_id] = Array();
                foreach ($this->regionList as $regionCode => $regionPriceId) {
                    $priceCollection[$bx_id][$regionCode] = '';
                }
            } else {
                $bx_id = $row['BX_ID'];
            }
            $rowRegionCode = $row['REGIONE'];

            if ($row['PRICE'] != '') {
                $priceCollection[$bx_id][$rowRegionCode] = $row['PRICE'];
            }
        }


        foreach ($priceCollection as $bx_id => &$priceRow) {
            foreach ($priceRow as $regionCode => $price) {
                if ($price == '') {
                    $priceRow[$regionCode] = $this->getDefaultPrice($priceRow);
                }
            }
        }

        return $priceCollection;
    }


    private function createNewBage($filename)
    {
        $csv = $this->getCSV($filename);
        $pre_new = $this->processCSV(
            $csv,
            Array(
                'ID_GSTORE' => 0,
                'REGIONE' => 1,
                $this->propList['HIT'] => 2//HIT
            )
        );

        foreach ($pre_new as &$row) {
            $row[$this->propList['HIT']] = 'Новинка';
        }

        $full_new = $this->glue($pre_new);
        $result = $this->getCSVTEXT(
            $full_new,
            Array(
                'BX_ID',
                $this->propList['HIT']
            )
        );

        return $result;
    }

    private function createBrandConnect($filename)
    {
        $this->loadBrands();

        $csv = $this->getCSV($filename);
        $process = $this->processCSV(
            $csv,
            Array(
                'ID_GSTORE' => 0,
                'BRAND' => 1
            )
        );

        foreach ($process as &$row) {
            $row[$this->propList['BRAND']] = $this->setBrand($row['BRAND']);
        }

        $final = $this->glue($process);
        $result = $this->getCSVTEXT(
            $final,
            Array(
                'BX_ID',
                $this->propList['BRAND']
            )
        );

        return $result;
    }

    private function loadBrands()
    {
        $query = CIBlockElement::getList(Array(), Array('IBLOCK_ID' => $this->BRAND_ID), false, false, Array('ID', 'IBLOCK_ID', 'CODE'));
        while ($item = $query->fetch()) {
            $this->brandList[$item['CODE']] = $item['ID'];
        }
    }

    private function setBrand($brandName)
    {
        if ($brandName == '0' || $brandName == '') {
            return '';
        }

        $params = Array(
            "max_len" => "100", // обрезает символьный код до 100 символов
            "change_case" => "L", // буквы преобразуются к нижнему регистру
            "replace_space" => "_", // меняем пробелы на нижнее подчеркивание
            "replace_other" => "_", // меняем левые символы на нижнее подчеркивание
            "delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
            "use_google" => "false", // отключаем использование google
        );

        $brandCode = CUtil::translit($brandName, $this->lang, $params);

        if (isset($this->brandList[$brandCode])) {
            return $this->brandList[$brandCode];
        }

        $query = CIBlockElement::getList(Array(), Array('IBLOCK_ID' => $this->BRAND_ID, 'CODE' => $brandCode), false, false, Array('ID', 'IBLOCK_ID'));

        if ($item = $query->fetch()) {
            $BRAND_ID = $item['ID'];
        } else {
            $el = new CIBlockElement();
            $newBrand = Array(
                'IBLOCK_ID' => $this->BRAND_ID,
                'ACTIVE' => 'Y',
                'NAME' => $brandName,
                'CODE' => $brandCode
            );

            $BRAND_ID = $el->Add($newBrand);
        }
        $this->brandList[$brandCode] = $BRAND_ID;
        return $BRAND_ID;
    }


    private function createProps($filename)
    {
        $csv = $this->getCSV($filename);
        $processed = $this->processCSV(
            $csv,
            Array(
                'REGIONE' => 0,
                'ID_GSTORE' => 1,
                $this->propList['BRAND'] => 11,//Бренд
                'IE_PREVIEW_TEXT' => 12,//Описание
                $this->propList['ENERGY'] => 13,//Энергетическая ценность
                $this->propList['PROTEIN'] => 14,//Белки
                $this->propList['FAT'] => 15,//Жиры
                $this->propList['CARBON'] => 16//Углеводы
            )
        );

        foreach ($processed as &$row) {
            $row[$this->propList['BRAND']] = $this->setBrand($row[$this->propList['BRAND']]);
        }


        $ready = $this->glue($processed);
        $result = $this->getCSVTEXT(
            $ready,
            Array(
                'BX_ID',
                $this->propList['BRAND'],
                'IE_PREVIEW_TEXT',
                $this->propList['ENERGY'],
                $this->propList['PROTEIN'],
                $this->propList['FAT'],
                $this->propList['CARBON']
            )
        );

        return $result;
    }

    private function mb_ucfirst($text)
    {
        return mb_substr($text, 0, 1) . mb_strtolower(mb_substr($text, 1));
    }

    private function createBrand($filename)
    {
        $csv = $this->getCSV($filename);
        $pre_brand = $this->processCSV(
            $csv,
            Array(
                'REGIONE' => 0,
                'ID_GSTORE' => 1,
                'IE_NAME' => 2,
                'IC_GROUP0' => 3,
                'IC_GROUP1' => 4,
                'IC_GROUP2' => 5,
                'IE_DETAIL_PICTURE' => 6,
                'CP_MEASURE' => 7,
                'PRICE' => 8,
                'STORE_CNT_1' => 9,
                'PROP_STRANA_PROIZV' => 10,
                'BRAND' => 11,
            )
        );

        $ar = Array();
        foreach ($pre_brand as $row) {
            if ($row['BRAND'] != '0' && $row['BRAND'] != '') {
                $ar[$row['BRAND']] = 1;

            }
        }
        $brandNameList = array_keys($ar);


        $brandQuery = CIBlockElement::getList(
            Array(),
            Array('IBLOCK_ID' => $this->BRAND_ID, 'NAME' => $brandNameList), false, false, Array('ID', 'IBLOCK_ID', 'NAME')
        );

        $existBrandList = Array();
        while ($brand = $brandQuery->fetch()) {
            $existBrandList[] = $brand['NAME'];
        }

        $brandNameList = array_diff($brandNameList, $existBrandList);

        $params = Array(
            "max_len" => "100", // обрезает символьный код до 100 символов
            "change_case" => "L", // буквы преобразуются к нижнему регистру
            "replace_space" => "_", // меняем пробелы на нижнее подчеркивание
            "replace_other" => "_", // меняем левые символы на нижнее подчеркивание
            "delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
            "use_google" => "false", // отключаем использование google
        );
        $el = new CIBlockElement();

        foreach ($brandNameList as $brandName) {
            $newBrand = Array(
                'IBLOCK_ID' => $this->BRAND_ID,
                'ACTIVE' => 'Y',
                'NAME' => $brandName,
                'CODE' => CUtil::translit($brandName, $this->lang, $params)
            );

            $el->Add($newBrand);
        }


        $full_brand = $this->glue($pre_brand);
        $brandNameList = array_keys($ar);
        $brandQuery = CIBlockElement::getList(
            Array(),
            Array('IBLOCK_ID' => $this->BRAND_ID, 'NAME' => $brandNameList), false, false, Array('ID', 'IBLOCK_ID', 'CODE')
        );


        $brandCodeList = Array();
        while ($item = $brandQuery->fetch()) {
            $brandCodeList[$item['CODE']] = $item['ID'];
        }

        foreach ($full_brand as $row) {
            $ID = $row['BX_ID'];
            $CODE = CUtil::translit($row['IE_NAME'], $this->lang, $params);
            $BRAND_ID = $brandCodeList[$CODE];


            if ($ID && $BRAND_ID) {
                $el->SetPropertyValuesEx(
                    $ID,
                    $this->CATALOG_ID,
                    Array('BRAND' => $BRAND_ID));
            }
        }
    }


    private function createFull($filename)
    {
        $this->loadBrands();

        $csv = $this->getCSV($filename);
        $pre_full = $this->processCSV(
            $csv,
            Array(
                'REGIONE' => 0,
                'ID_GSTORE' => 1,
                'IE_NAME' => 2,
                'IC_GROUP0' => 3,
                'IC_GROUP1' => 4,
                'IC_GROUP2' => 5,
                'IE_DETAIL_PICTURE' => 6,
                'CP_MEASURE' => 7,
                'PRICE' => 8,
                'STORE_CNT_1' => 9,
                'PROP_STRANA_PROIZV' => 10,
                $this->propList['BRAND'] => 11,//BRAND
            )
        );

        foreach ($pre_full as &$row) {
            $row['IC_GROUP0'] = $this->mb_ucfirst($row['IC_GROUP0']);
            $row['IC_GROUP1'] = $this->mb_ucfirst($row['IC_GROUP1']);
            $row['IC_GROUP2'] = $this->mb_ucfirst($row['IC_GROUP2']);
            $row['IE_DETAIL_PICTURE'] = "{$row['ID_GSTORE']}.jpg";

            switch ($row['CP_MEASURE']) {
                case('шт'): {
                    $row['MEASURE_RATIO'] = '1';
                }
                    break;
                case('кг'): {
                    $row['MEASURE_RATIO'] = '0.1';
                }
                    break;
                case('литр'): {
                    $row['MEASURE_RATIO'] = '0.5';
                }
                    break;
                default: {
                    $row['MEASURE_RATIO'] = '';
                }
            }

            if (trim($row['PROP_STRANA_PROIZV']) == '0') {
                $row['PROP_STRANA_PROIZV'] = '';
            }
            $row[$this->propList['BRAND']] = $this->setBrand($row[$this->propList['BRAND']]);
        }

        $full_left = $this->glue($pre_full, false);

        $full_left = $this->pushPrices($full_left);

        $outputArray = Array(
            'BX_ID',
            'REGIONE',
            'ID_GSTORE',
            'IE_NAME',
            'IC_GROUP0',
            'IC_GROUP1',
            'IC_GROUP2',
            'IE_DETAIL_PICTURE',
            'CP_MEASURE',
            'MEASURE_RATIO',
            'STORE_CNT_1',
            'PROP_STRANA_PROIZV',
            $this->propList['BRAND']
        );

        $outputArray = $this->fillOuputArray($outputArray);

        $result = $this->getCSVTEXT(
            $full_left,
            $outputArray,
            false
        );
        return $result;

    }

    private function createLeftovers($filename)
    {
        $csv = $this->getCSV($filename);
        $pre_left = $this->processCSV(
            $csv,
            Array(
                'ID_GSTORE' => 0,
                'REGIONE' => 1,
                'PRICE' => 2,
                'STORE_CNT_1' => 3
            )
        );

        foreach ($pre_left as &$row) {
            $row['STORE_CNT_1'] = str_ireplace(',', '.', $row['STORE_CNT_1']);
        }

        $full_left = $this->glue($pre_left);

        $full_left = $this->pushPrices($full_left);

        $outputArray = Array(
            'BX_ID',
            'REGIONE',
            'STORE_CNT_1',
        );

        $outputArray = $this->fillOuputArray($outputArray);

        $result = $this->getCSVTEXT(
            $full_left,
            $outputArray
        );
        return $result;
    }
}

?>