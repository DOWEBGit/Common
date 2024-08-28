<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("expires: -1");

include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/AreeControlliEnum.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/Controller.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/EtichetteEnum.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/GetLink.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/PagineControlliEnum.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/PagineDatiControlliEnum.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/PagineDatiEnum.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/PagineEnum.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/ModelEnum.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/PagineInterneEnum.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/Model.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/public/php/Common/CodeGenerator/Replace.php');