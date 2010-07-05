<?php
//This is an example script for serialized templates

//You can ONLY use serialized templates when the included templates are always
//the same (no dynamic includes).
//
//You should run a script like below if you changed one or more of the used 
//templates and run it only once. Do NOT run this script with each 
//browser request!

require_once( "./mod.TPLSerializer.inc.php" );

$stpl = new TPLSerializer( "./tpmain.tpl", "./tpmain.stpl" );
$stpl->assignInclude("main", "./tptable.tpl");
$stpl->assignInclude("rows", "./tprow.tpl");

//create the file ./tpmain.stpl
//IMPORTANT NOTE: make sure the target dir is writable by PHP
$stpl->doSerialize(); 

//open useSerialized.php to see how to use the serialized template 
?>