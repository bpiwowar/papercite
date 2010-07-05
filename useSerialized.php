<?php
//This is an example script for serialized templates

include ('./class.TemplatePower.inc.php');

$tpl = new TemplatePower("./tpmain.stpl");
$tpl->serializedBase()

//You don't need to use the assignInclude function anymore.
//If you need to add an include template, add it in the script
//which creates the serialized templates and run it (open makeSerialed.php
//to see an example of this script). After running you can 
//access it in this script.

$tpl->prepare()

/* do the things you normally do here */

$tpl->printToScreen()
?>