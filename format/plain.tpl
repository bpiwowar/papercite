<!--

Template for the 'plain' style, which aims to mimic the one available in
LaTeX distributions (plain.bst).
Cross-reference field 'crossref' is not supported: all fields must be inlined.

Author: (c) Andrius Velykis, 2012
http://andrius.velykis.lt

-->
<formats>

<property name="titleCapitalization" value="0"/>
<property name="primaryCreatorFirstStyle" value="0"/>
<property name="primaryCreatorOtherStyle" value="0"/>
<property name="primaryCreatorInitials" value="0"/>
<property name="primaryCreatorFirstName" value="0"/>
<property name="otherCreatorFirstStyle" value="1"/>
<property name="otherCreatorOtherStyle" value="1"/>
<property name="otherCreatorInitials" value="0"/>
<property name="dayFormat" value="0"/>
<property name="otherCreatorFirstName" value="1"/>
<property name="primaryCreatorList" value="1"/>
<property name="otherCreatorList" value="1"/>
<property name="monthFormat" value="1"/>
<property name="editionFormat" value="1"/>
<property name="primaryCreatorListMore" value="100"/>
<property name="primaryCreatorListLimit" value="100"/>
<property name="dateFormat" value="1"/>
<property name="primaryCreatorListAbbreviation" value=", et al"/>
<property name="otherCreatorListMore" value="100"/>
<property name="runningTimeFormat" value="1"/>
<property name="primaryCreatorRepeatString" value=""/>
<property name="primaryCreatorRepeat" value="0"/>
<property name="otherCreatorListLimit" value="100"/>
<property name="otherCreatorListAbbreviation" value=", et al."/>
<property name="pageFormat" value="2"/>
<property name="editorSwitch" value="1"/>
<property name="editorSwitchIfYes" value="editor (^Ed.^Eds.^), "/>
<property name="primaryCreatorUppercase" value="on"/>
<property name="otherCreatorUppercase" value="on"/>
<property name="primaryCreatorSepFirstBetween" value=", "/>
<property name="primaryCreatorSepNextBetween" value=", "/>
<property name="primaryCreatorSepNextLast" value=", and "/>
<property name="otherCreatorSepFirstBetween" value=", "/>
<property name="otherCreatorSepNextBetween" value=", "/>
<property name="otherCreatorSepNextLast" value=" &amp; "/>
<property name="primaryTwoCreatorsSep" value=" and "/>
<property name="otherTwoCreatorsSep" value=" &amp; "/>
<property name="userMonth_1" value=""/>
<property name="userMonth_2" value=""/>
<property name="userMonth_3" value=""/>
<property name="userMonth_4" value=""/>
<property name="userMonth_5" value=""/>
<property name="userMonth_6" value=""/>
<property name="userMonth_7" value=""/>
<property name="userMonth_8" value=""/>
<property name="userMonth_9" value=""/>
<property name="userMonth_10" value=""/>
<property name="userMonth_11" value=""/>
<property name="userMonth_12" value=""/>
<property name="dateRangeDelimit1" value="-"/>
<property name="dateRangeDelimit2" value="-"/>
<property name="dateRangeSameMonth" value="1"/>


<format types="proceedings">
   @?editor@<!--
	-->@editor@<!--
	-->@?editor~\band\b@<!-- Look for 'and' words, then multiple editors
		-->, editors<!--
	-->@:editor@<!--
		-->, editor<!--
	-->@;editor@<!--
	-->. <!--
-->@:editor@<!--
	-->@?organization@<!--
		-->@organization@. <!--
	-->@;organization@<!--
-->@;editor@<!--
-->@?title@<!--
	-->&lt;em&gt;@title@&lt;/em&gt;<!--
-->@;title@<!--
-->@?volume@<!--
	-->, volume @volume@<!--
	-->@?series@<!--
		--> of &lt;em&gt;@series@&lt;/em&gt;<!--
	-->@;series@<!--
-->@:volume@<!--
	-->@?number@<!--
		-->, number @number@<!--
		-->@?series@<!--
			--> in @series@<!--
		-->@;series@<!--
	-->@:number@<!--
		-->@?series@<!--
			-->, @series@<!--
		-->@;series@<!--
	-->@;number@<!--
-->@;volume@<!--
-->@?address@<!--
	-->, @address@<!--
	-->@?year@<!--
		-->, <!--
		-->@?month@<!--
			-->@month@ <!--
		-->@;month@<!--
		-->@year@<!--
	-->@;year@<!--
	-->. <!--
	-->@?editor@<!--
		-->@?organization@<!--
			-->@organization@<!--
			-->@?publisher@<!--
				-->, <!--
			-->@;publisher@<!--
		-->@;organization@<!--
	-->@;editor@<!--
	-->@?publisher@<!--
		-->@publisher@<!--
	-->@;publisher@<!--
-->@:address@<!--
	-->@?editor@<!--
		-->@?organization@<!--
			-->. @organization@<!--
			-->@?publisher@<!--
				-->, <!--
			-->@;publisher@<!--
		-->@;organization@<!--
	-->@:editor@<!--
		-->@?publisher@<!--
			-->. <!--
		-->@;publisher@<!--
	-->@;editor@<!--
	-->@?publisher@<!--
		-->@publisher@<!--
	-->@;publisher@<!--
	-->@?year@<!--
		-->, <!--
		-->@?month@<!--
			-->@month@ <!--
		-->@;month@<!--
		-->@year@<!--
	-->@;year@<!--
-->@;address@<!--
-->.<!--
-->@?note@<!--
	--> @note@<!--
-->@;note@
</format>

<format types="unpublished">
   @?author@<!--
	-->@author@. <!--
-->@;author@<!--
-->@?title@<!--
	-->@title@. <!--
-->@;title@<!--
-->@?note@<!--
	-->@note@<!--
-->@;note@<!--
-->@?year@<!--
	-->@?note@<!--
		-->@?note~\p{Po}$@<!-- If note ends with punctuation character, do not add a comma
			--> <!--
		-->@:note@<!--
			-->, <!--
		-->@;note@<!--
	-->@;note@<!--
	-->@?month@<!--
		-->@month@ <!--
	-->@;month@<!--
	-->@year@<!--
	-->.<!--
-->@;year@
</format>

<format types="misc booklet">
   @?author@<!--
	-->@author@. <!--
-->@;author@<!--
-->@?title@<!--
	-->@title@. <!--
-->@;title@<!--
-->@?howpublished@<!--
	-->@howpublished@<!--
-->@;howpublished@<!--
-->@?entrytype=booklet@<!-- Add address for "booklet"
	-->@?address@<!--
		-->@?howpublished@<!--
			-->, <!--
		-->@;howpublished@<!--
		-->@address@<!--
	-->@;address@<!--
-->@;entrytype@<!--
-->@?year@<!--
	-->@?howpublished@<!--
		-->, <!--
	-->@:howpublished@<!--
	-->@?entrytype=booklet@<!-- Need comma if address was available for "booklet"
		-->@?address@<!--
			-->, <!--
		-->@;address@<!--
	-->@;entrytype@<!--
	-->@;howpublished@<!--
	-->@?month@<!--
		-->@month@ <!--
	-->@;month@<!--
	-->@year@<!--
-->@;year@<!--
-->.<!--
-->@?note@<!--
	--> @note@<!--
-->@;note@
</format>

<format types="inproceedings incollection">
   @?author@<!--
	-->@author@. <!--
-->@;author@<!--
-->@?title@<!--
	-->@title@. <!--
-->@;title@<!--
-->@?booktitle@<!--
	-->In <!--
	-->@?editor@<!--
		-->@editor@<!--
		-->@?editor~\band\b@<!-- Look for 'and' words, then multiple editors
			-->, editors<!--
		-->@:editor@<!--
			-->, editor<!--
		-->@;editor@<!--
		-->, <!--
	-->@;editor@<!--
	-->&lt;em&gt;@booktitle@&lt;/em&gt;<!--
-->@;booktitle@<!--
-->@?volume@<!--
	-->, volume @volume@<!--
	-->@?series@<!--
		--> of &lt;em&gt;@series@&lt;/em&gt;<!--
	-->@;series@<!--
-->@:volume@<!--
	-->@?number@<!--
		-->, number @number@<!--
		-->@?series@<!--
			--> in @series@<!--
		-->@;series@<!--
	-->@:number@<!--
		-->@?series@<!--
			-->, @series@<!--
		-->@;series@<!--
	-->@;number@<!--
-->@;volume@<!--
-->@?entrytype=incollection@<!-- Add chapters for "incollection"
	-->@?chapter@<!--
		-->, chapter @chapter@<!--
	-->@;chapter@<!--
-->@;entrytype@<!--
-->@?pages@<!--
	-->@?pages~\b\D\b@<!-- Look for not digit, then multi page
		-->, pages @pages@<!--
	-->@:pages@<!--
		-->, page @pages@<!--
	-->@;pages@<!--
-->@;pages@<!--
-->@?entrytype=incollection@<!-- Different ending for "incollection"
	-->. <!--
	-->@?publisher@<!--
		-->@publisher@<!--
	-->@;publisher@<!--
	-->@?address@<!--
		-->, @address@<!--
	-->@;address@<!--
	-->@?edition@<!--
		-->, @edition@ edition<!--
	-->@;edition@<!--
	-->@?year@<!--
		-->, <!--
		-->@?month@<!--
			-->@month@ <!--
		-->@;month@<!--
		-->@year@<!--
	-->@;year@<!--
-->@;entrytype@<!--
-->@?entrytype=inproceedings@<!-- Different ending for "inproceedings"
	-->@?address@<!--
		-->, @address@<!--
		-->@?year@<!--
			-->, <!--
			-->@?month@<!--
				-->@month@ <!--
			-->@;month@<!--
			-->@year@<!--
		-->@;year@<!--
		-->@?organization||publisher@<!--
			-->. <!--
		-->@;organization||publisher@<!--
		-->@?organization@<!--
			-->@organization@<!--
		-->@;organization@<!--
		-->@?publisher@<!--
			-->@?organization@<!--
				-->, <!--
			-->@;organization@<!--
			-->@publisher@<!--
		-->@;publisher@<!--
	-->@:address@<!--
		-->@?organization||publisher@<!--
			-->. <!--
		-->@;organization||publisher@<!--
		-->@?organization@<!--
			-->@organization@,<!--
		-->@;organization@<!--
		-->@?publisher@<!--
			--> @publisher@<!--
		-->@;publisher@<!--
		-->@?year@<!--
			-->, <!--
			-->@?month@<!--
				-->@month@ <!--
			-->@;month@<!--
			-->@year@<!--
		-->@;year@<!--
	-->@;address@<!--
-->@;entrytype@<!--
-->.<!--
-->@?note@<!--
	--> @note@<!--
-->@;note@
</format>

<format types="techreport">
   @?author@<!--
	-->@author@. <!--
-->@;author@<!--
-->@?title@<!--
	-->@title@. <!--
-->@;title@<!--
-->@?type@<!--
	-->@type@<!--
-->@:type@<!--
	-->Technical Report<!--
-->@;type@<!--
-->@?number@<!--
	--> @number@<!--
-->@;number@<!--
-->@?institution@<!--
	-->, @institution@<!--
-->@;institution@<!--
-->@?address@<!--
	-->, @address@<!--
-->@;address@<!--
-->@?year@<!--
	-->, <!--
	-->@?month@<!--
		-->@month@ <!--
	-->@;month@<!--
	-->@year@<!--
-->@;year@<!--
-->.<!--
-->@?note@<!--
	--> @note@<!--
-->@;note@
</format>

<format types="book inbook">
   @?author@<!--
	-->@author@. <!--
-->@:author@<!--
	-->@?editor@<!--
		-->@editor@<!--
		-->@?editor~\band\b@<!-- Look for 'and' words, then multiple editors
			-->, editors<!--
		-->@:editor@<!--
			-->, editor<!--
		-->@;editor@<!--
		-->. <!--
	-->@;editor@<!--
-->@;author@<!--
-->@?title@<!--
	-->&lt;em&gt;@title@&lt;/em&gt;<!--
-->@;title@<!--
-->@?volume@<!--
	-->, volume @volume@<!--
	-->@?series@<!--
		--> of &lt;em&gt;@series@&lt;/em&gt;<!--
	-->@;series@<!--
-->@;volume@<!--
-->@?entrytype=inbook@<!-- Add chapters for "inbook"
	-->@?chapter@<!--
		-->, chapter @chapter@<!--
	-->@;chapter@<!--
	-->@?pages@<!--
		-->@?pages~\b\D\b@<!-- Look for not digit, then multi page
			-->, pages @pages@<!--
		-->@:pages@<!--
			-->, page @pages@<!--
		-->@;pages@<!--
	-->@;pages@<!--
-->@;entrytype@<!--
-->@?volume@<!-- Only the "false" case here
-->@:volume@<!--
	-->@?number@<!--
		-->. Number @number@<!--
		-->@?series@<!--
			--> in @series@<!--
		-->@;series@<!--
	-->@:number@<!--
		-->@?series@<!--
			-->. @series@<!--
		-->@;series@<!--
	-->@;number@<!--
-->@;volume@<!--
-->. <!--
-->@?publisher@<!--
	-->@publisher@<!--
-->@;publisher@<!--
-->@?address@<!--
	-->, @address@<!--
-->@;address@<!--
-->@?edition@<!--
	-->, @edition@ edition<!--
-->@;edition@<!--
-->@?year@<!--
	-->, <!--
	-->@?month@<!--
		-->@month@ <!--
	-->@;month@<!--
	-->@year@<!--
-->@;year@<!--
-->.<!--
-->@?note@<!--
	--> @note@<!--
-->@;note@
</format>

<format types="article #">
   @?author@<!--
	-->@author@. <!--
-->@;author@<!--
-->@?title@<!--
	-->@title@. <!--
-->@;title@<!--
-->@?journal@<!--
	-->&lt;em&gt;@journal@&lt;/em&gt;<!--
-->@;journal@<!--
-->@?volume||number||pages@<!--
	-->, <!--
-->@;volume||number||pages@<!--
-->@?volume@<!--
	-->@volume@<!--
-->@;volume@<!--
-->@?number@<!--
	-->(@number@)<!--
-->@;number@<!--
-->@?pages@<!--
	-->@?volume||number@<!--
		-->:<!--
	-->@:volume||number@<!--
		-->@?pages~\b\D\b@<!-- Look for not digit, then multi page
			-->pages <!--
		-->@:pages@<!--
			-->page <!--
		-->@;pages@<!--
	-->@;volume||number@<!--
	-->@pages@<!--
-->@;pages@<!--
-->@?year@<!--
	-->, <!--
	-->@?month@<!--
		-->@month@ <!--
	-->@;month@<!--
	-->@year@<!--
-->@;year@<!--
-->.<!--
-->@?note@<!--
	--> @note@<!--
-->@;note@
</format>

<format types="phdthesis mastersthesis">
   @?author@<!--
	-->@author@. <!--
-->@;author@<!--
-->@?entrytype=phdthesis@<!-- PhD has emphasized title, Master normal
	-->@?title@<!--
		-->&lt;em&gt;@title@&lt;/em&gt;. <!--
	-->@;title@<!--
	-->PhD thesis<!--
-->@;entrytype@<!--
-->@?entrytype=mastersthesis@<!--
	-->@?title@<!--
		-->@title@. <!--
	-->@;title@<!--
	-->Master's thesis<!--
-->@;entrytype@<!--
-->@?type@<!--
	--> @type@<!--
-->@;type@<!--
-->@?school@<!--
	-->, @school@<!--
-->@;school@<!--
-->@?address@<!--
	-->, @address@<!--
-->@;address@<!--
-->@?year@<!--
	-->, <!--
	-->@?month@<!--
		-->@month@ <!--
	-->@;month@<!--
	-->@year@<!--
-->@;year@<!--
-->.<!--
-->@?note@<!--
	--> @note@<!--
-->@;note@
</format>

<format types="manual">
   @?author@<!--
	-->@author@. <!--
-->@:author@<!--
	-->@?organization@<!--
		-->@organization@<!--
		-->@?address@<!--
			-->, @address@<!--
		-->@;address@<!--
		-->. <!--
	-->@;organization@<!--
-->@;author@<!--
-->@?title@<!--
	-->&lt;em&gt;@title@&lt;/em&gt;. <!--
-->@;title@<!--
-->@?author@<!--
	-->@?organization@<!--
		-->@organization@<!--
		-->@?address@<!--
			-->, @address@<!--
		-->@;address@<!--
		-->. <!--
	-->@;organization@<!--
-->@:author@<!--
	-->@?organization@<!--
	-->@:organization@<!-- If empty
		-->@?address@<!--
			-->@address@. <!--
		-->@;address@<!--
	-->@;organization@<!--
-->@;author@<!--
-->@?edition@<!--
	-->@edition@ edition<!--
-->@;edition@<!--
-->@?year@<!--
	-->, <!--
	-->@?month@<!--
		-->@month@ <!--
	-->@;month@<!--
	-->@year@<!--
-->@;year@<!--
-->.<!--
-->@?note@<!--
	--> @note@<!--
-->@;note@
</format>


</formats>
