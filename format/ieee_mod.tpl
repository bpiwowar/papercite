<!-- 

@author Jaroslav Vitku

Purpose:
       We do not have MASTERSTHESIS, but we have Diploma and Bachelor Theses, these will be stored as MASTERSTHESIS

Changes:
       -MASTERTHESIS and PHDTHESIS are separated
       -field NOTE added on multiple places (for ISBN number or thesis supervisor)

-->
<formats>

<property name="titleCapitalization" value="0"/>
<property name="primaryCreatorFirstStyle" value="0"/>
<property name="primaryCreatorOtherStyle" value="0"/>
<property name="primaryCreatorInitials" value="0"/>
<property name="primaryCreatorFirstName" value="1"/>
<property name="otherCreatorFirstStyle" value="1"/>
<property name="otherCreatorOtherStyle" value="1"/>
<property name="otherCreatorInitials" value="0"/>
<property name="dayFormat" value="0"/>
<property name="otherCreatorFirstName" value="1"/>
<property name="primaryCreatorList" value="0"/>
<property name="otherCreatorList" value="0"/>
<property name="monthFormat" value="2"/>
<property name="editionFormat" value="1"/>
<property name="primaryCreatorListMore" value=""/>
<property name="primaryCreatorListLimit" value=""/>
<property name="dateFormat" value="1"/>
<property name="primaryCreatorListAbbreviation" value=""/>
<property name="otherCreatorListMore" value=""/>
<property name="runningTimeFormat" value="0"/>
<property name="primaryCreatorRepeatString" value=""/>
<property name="primaryCreatorRepeat" value="0"/>
<property name="otherCreatorListLimit" value=""/>
<property name="otherCreatorListAbbreviation" value=""/>
<property name="pageFormat" value="2"/>
<property name="editorSwitch" value="0"/>
<property name="editorSwitchIfYes" value=""/>
<property name="primaryCreatorSepFirstBetween" value=", "/>
<property name="primaryCreatorSepNextBetween" value=", "/>
<property name="primaryCreatorSepNextLast" value=", and "/>
<property name="otherCreatorSepFirstBetween" value=", "/>
<property name="otherCreatorSepNextBetween" value=", "/>
<property name="otherCreatorSepNextLast" value=", and "/>
<property name="primaryTwoCreatorsSep" value=" and "/>
<property name="otherTwoCreatorsSep" value=" and "/>
<property name="userMonth_1" value="Jan."/>
<property name="userMonth_2" value="Feb."/>
<property name="userMonth_3" value="Mar."/>
<property name="userMonth_4" value="Apr."/>
<property name="userMonth_5" value="May"/>
<property name="userMonth_6" value="June"/>
<property name="userMonth_7" value="July"/>
<property name="userMonth_8" value="Aug."/>
<property name="userMonth_9" value="Sept."/>
<property name="userMonth_10" value="Oct."/>
<property name="userMonth_11" value="Nov."/>
<property name="userMonth_12" value="Dec."/>
<property name="dateRangeDelimit1" value="-"/>
<property name="dateRangeDelimit2" value="/"/>
<property name="dateRangeSameMonth" value="1"/>

<format types="proceedings unpublished misc">
@?author@@author@, @;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt;@;@@?howpublished@, @howpublished@@;@ @?address@@address@: @;@@?publisher@@publisher@@;@@?note@, @note@@;@@?month@, @month@@;@@?year@, @year@@;@.
</format>

<format types="book">
@?author@@author@, @;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt;@;@@?edition@, @edition@ ed.@;@@?editor@, @editor@, @?#editor&gt;1@Eds@:editor@Ed@;editor@.@;@@?address||publisher||year||volume@, @:@@;@@?address@@address@: @;@@?publisher@@publisher@@;@@?year@, @year@@;@@?volume@, vol. @volume@@;@@?address||publisher||year||volume@@:@@;@@?note@, @note@@;@.
</format>

<format types="inbook incollection">
@?author@@author@, @;@@?title@&quot;@title@@;@@?booktitle||edition||editor@,&quot; @:@.&quot; @;@@?booktitle@in &lt;span style=&quot;font-style: italic&quot;&gt;@booktitle@&lt;/span&gt;@;@@?edition@, @edition@ ed.@;@@?editor@, @editor@, @?#editor&gt;1@Eds@:editor@Ed@;editor@.@;@@?booktitle||edition||editor@, @:@@;@@?address@@address@: @;@@?publisher@@publisher@@;@@?year@, @year@@;@@?volume@, vol. @volume@@;@@?pages@, @?#pages&gt;1@pp. @:pages@p. @;pages@@pages@@;@@?note@, @note@@;@.
</format>

<format types="article #">
@?author@@author@, @;@@?title@&quot;@title@@;@@?journal||volume||number||pages||year@,&quot; @:@.&quot; @;@@?journal@&lt;span style=&quot;font-style: italic&quot;&gt;@journal@&lt;/span&gt;@;@@?volume@, vol. @volume@@;@@?number@, iss. @number@@;@@?pages@, @?#pages&gt;1@pp. @:pages@p. @;pages@@pages@@;@@?year@, @year@@;@@?journal||volume||number||pages||year@.@:@@;@
</format>

<format types="inproceedings">
@?author@@author@, @;@@?title@&quot;@title@@;@@?booktitle||address@,&quot; @:@.&quot; @;@@?booktitle@in &lt;span style=&quot;font-style: italic&quot;&gt;@booktitle@&lt;/span&gt;@;@@?address@, @address@@;@@?booktitle||address@, @:@@;@@?date@@date@@;@@?year@ @year@@;@@?pages@, @?#pages&gt;1@pp. @:pages@p. @;pages@@pages@@;@@?note@, @note@@;@.
</format>

<format types="phdthesis">
@?author@@author@, @;@@?title@&quot;@title@,&quot; @;@@?type@@type@ @;@@?entrytype=phdthesis@@?entrytype=phdthesis@PhD Thesis@;@@?school@, @school@@;@@?address@, @address@@;@@?note@, @note@@;@@?year@, @year@@;@.
</format>

<format types="mastersthesis">
@?author@@author@, @;@@?title@&quot;@title@,&quot; @;@@?type@@type@@;@@?school@, @school@@;@@?address@, @address@@;@@?note@, @note@@;@@?year@, @year@@;@.
</format>

<format types="techreport">
@?author@@author@, @;@@?title@&quot;@title@,&quot; @;@@?institution@@institution@@;@@?address@, @address@@;@@?type||number@, @:@@;@@?type@@type@ @;@@?number@@number@@;@@?type||number@, @:@@;@@?date@@date@ @;@@?year@, @year@@;@.
</format>

</formats>
