/********************************
OSBib:
A collection of PHP classes to create and manage bibliographic formatting for OS bibliography software 
using the OSBib standard.

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net 
so that your improvements can be added to the release package.

Adapted from WIKINDX: http://wikindx.sourceforge.net

Mark Grimshaw 2005
http://bibliophile.sourceforge.net

	$Header: /cvsroot/bibliophile/OSBib/create/common.js,v 1.3 2005/06/29 20:22:05 sirfragalot Exp $
********************************/

function init(){ // init() is called in body.tpl
 initPreviewLinks();
}

/**
* Create the preview link for bibliographic style editing, hiding the link for non-javascript-enabled browsers.
* 
* @author	Jess Collicott
* @editor	Mark Tsikanovski and Mark Grimshaw
* @version	2
*/
function initPreviewLinks(){ // collect any links for style template preview, add onclick events and make them visible
 var previewLinkKeyString = 'action=previewStyle'; // use this string to detect Preview links
 var previewLinkKeyRegEx = new RegExp(previewLinkKeyString,'i');
 var links = document.getElementsByTagName('body').item(0).getElementsByTagName('a'); // get collection of all links
 var linksLength = links.length; // cache

// As of 3.1, style previewing is not working in IE so turn it off all together.
var agt = navigator.userAgent.toLowerCase();
  var is_ie = ((agt.indexOf("msie") != -1));
  if (is_ie)
  {
	for (i=0;i<linksLength;i++)
	{
		if (typeof(links[i].href) != 'undefined' && links[i].href.search(previewLinkKeyRegEx) != -1)
		{
			links[i].className = 'linkHidden';
		}
	}
	return;
  }
  
 for (i=0;i<linksLength;i++){
  if (typeof(links[i].href) != 'undefined' && links[i].href.search(previewLinkKeyRegEx) != -1){
    if (links[i].className == 'imgLink linkHidden') {
	  links[i].className = 'imgLink linkCite';
	}
	else {
      links[i].className = 'link linkCite';
	}
  }
 }
}

/**
* pop-up window for style previews
* 
* @Author Mark Grimshaw with a lot of help from Christian Boulanger
*/
function openPopUpStylePreview(url, height, width, templateName)
{
	var fieldArray = new Array ("style_titleCapitalization", "style_primaryCreatorFirstStyle", 
			"style_primaryCreatorOtherStyle", "style_primaryCreatorInitials", 
			"style_primaryCreatorFirstName", "style_otherCreatorFirstStyle", 
			"style_otherCreatorOtherStyle", "style_otherCreatorInitials", "style_dayFormat", 
			"style_otherCreatorFirstName", "style_primaryCreatorList", "style_otherCreatorList",
			"style_primaryCreatorListAbbreviationItalic", "style_otherCreatorListAbbreviationItalic", 
			"style_monthFormat", "style_editionFormat", "style_primaryCreatorListMore", 
			"style_primaryCreatorListLimit", "style_dateFormat", 
			"style_primaryCreatorListAbbreviation", "style_otherCreatorListMore", 
			"style_runningTimeFormat", "style_primaryCreatorRepeatString", "style_primaryCreatorRepeat", 
			"style_otherCreatorListLimit", "style_otherCreatorListAbbreviation", "style_pageFormat", 
			"style_editorSwitch", "style_editorSwitchIfYes", "style_primaryCreatorUppercase", 
			"style_otherCreatorUppercase", "style_primaryCreatorSepFirstBetween", 
			"style_primaryCreatorSepNextBetween", "style_primaryCreatorSepNextLast", 
			"style_otherCreatorSepFirstBetween", "style_otherCreatorSepNextBetween", 
			"style_otherCreatorSepNextLast", "style_primaryTwoCreatorsSep", "style_otherTwoCreatorsSep", 
			"style_userMonth_1", "style_userMonth_2", "style_userMonth_3", "style_userMonth_4", 
			"style_userMonth_5", "style_userMonth_6", "style_userMonth_7", "style_userMonth_8", 
			"style_userMonth_9", "style_userMonth_10", "style_userMonth_11", "style_userMonth_12", 
			"style_dateRangeDelimit1", "style_dateRangeDelimit2", "style_dateRangeSameMonth"
		);
	var styleArray = new Array ();
	for (index = 0; index < fieldArray.length; index++)
	{
		var currFormField = document.forms[0][fieldArray[index]];
		if ((currFormField.type == "checkbox") && currFormField.checked)
			styleArray[fieldArray[index]] = "on"; // checkbox
		else if (currFormField.type != "checkbox")
			styleArray[fieldArray[index]] = currFormField.value; // input and textarea
    }
    var a_php = "";
    var total = 0;
    for (var key in styleArray)
    {
        ++ total;
        a_php = a_php + "s:" +
                String(key).length + ":\"" + String(key) + "\";s:" +
                String(styleArray[key]).length + ":\"" + String(styleArray[key]) + "\";";
    }
    a_php = "a:" + total + ":{" + a_php + "}";
    url = url + "&style=" + escape(a_php);
	var templateString = document.forms[0][templateName].value; 
	url = url +"&templateName=" + escape(templateName) + "&templateString=" + escape(templateString);
	var popUp = window.open(url,'popUp','height='+height,'width='+width,'status,scrollbars,resizable,dependent');
}
/* ===== common JavaScript functions ===== */

// placeholder
