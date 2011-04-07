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
@?author@@author@, @;author@@?title@<span style="font-style: italic">@title@</span>@;title@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@@?year@, @year@@;year@.
</format>

<format types="book">
@?author@@author@, @;author@@?title@<span style="font-style: italic">@title@</span>@;title@@?edition@, @edition@ ed.@;edition@@?editor@, @editor@, @?#editor>1@Eds@:editor@Ed@;editor@.@;editor@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@@?year@, @year@@;year@@?volume@, vol. @volume@@;volume@
</format>

<format types="inbook">
@?author@@author@, @;author@@?title@"@title@@;title@@?bookitle@in <span style="font-style: italic">@bookitle@</span>@;bookitle@@?edition@, @edition@ ed.@;edition@@?editor@, @editor@, @?#editor>1@Eds@:editor@Ed@;editor@.@;editor@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@@?year@, @year@@;year@@?volume@, vol. @volume@@;volume@@?pages@, @?#pages>1@pp. @:pages@p. @;pages@@pages@@;pages@.
</format>

<format types="article #">
@?author@@author@, @;author@@?title@"@title@@;title@@?journal@<span style="font-style: italic">@journal@</span>@;journal@@?volume@, vol. @volume@@;volume@@?number@, iss. @number@@;number@@?pages@, @?#pages>1@pp. @:pages@p. @;pages@@pages@@;pages@@?year@, @year@@;year@
</format>

<format types="inproceedings">
@?author@@author@, @;author@@?title@"@title@@;title@@?booktitle@in <span style="font-style: italic">@booktitle@</span>@;booktitle@@?address@, @address@@;address@@?date@@date@@;date@@?year@ @year@@;year@@?pages@, @?#pages>1@pp. @:pages@p. @;pages@@pages@@;pages@.
</format>

<format types="phdthesis">
@?author@@author@, @;author@@?title@"@title@," @;title@@?type@@type@ @;type@@?institution@, @institution@@;institution@@?address@, @address@@;address@@?year@, @year@@;year@.
</format>

<format types="techreport">
@?author@@author@, @;author@@?title@"@title@," @;title@@?institution@@institution@@;institution@@?address@, @address@@;address@@?type@@type@ @;type@@?number@@number@@;number@@?date@@date@ @;date@@?year@@year@@;year@.
</format>

</formats>
