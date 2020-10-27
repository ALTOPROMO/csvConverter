<?php
class leadercsvprepare extends CModule
{
	const MODULE_ID = 'leadercsvprepare';
	var $MODULE_ID = "leadercsvprepare";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	function leadercsvprepare()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = "модуль подготовки CSV к импорту";
		$this->MODULE_DESCRIPTION = "модуль подготовки CSV к импорту";
	}

	function InstallFiles()
	{
    	return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		$this->InstallFiles();
		RegisterModule("leadercsvprepare");
		$APPLICATION->IncludeAdminFile("Установка модуля leadercsvprepare", $DOCUMENT_ROOT."/local/modules/leadercsvprepare/install/step.php");
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		$this->UnInstallFiles();
		UnRegisterModule("leadercsvprepare");
		$APPLICATION->IncludeAdminFile("Деинсталляция модуля leadercsvprepare", $DOCUMENT_ROOT."/local/modules/leadercsvprepare/install/unstep.php");
	}
}
?>