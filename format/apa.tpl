<formats>

<property name="titleCapitalization" value="0"/>
<property name="primaryCreatorFirstStyle" value="1"/>
<property name="primaryCreatorOtherStyle" value="1"/>
<property name="primaryCreatorInitials" value="0"/>
<property name="primaryCreatorFirstName" value="1"/>
<property name="otherCreatorFirstStyle" value="0"/>
<property name="otherCreatorOtherStyle" value="0"/>
<property name="otherCreatorInitials" value="0"/>
<property name="dayFormat" value="0"/>
<property name="otherCreatorFirstName" value="1"/>
<property name="primaryCreatorList" value="1"/>
<property name="otherCreatorList" value="0"/>
<property name="monthFormat" value="1"/>
<property name="editionFormat" value="1"/>
<property name="primaryCreatorListMore" value="7"/>
<property name="primaryCreatorListLimit" value="6"/>
<property name="dateFormat" value="1"/>
<property name="primaryCreatorListAbbreviation" value=", et al."/>
<property name="otherCreatorListMore" value=""/>
<property name="runningTimeFormat" value="1"/>
<property name="primaryCreatorRepeatString" value=""/>
<property name="primaryCreatorRepeat" value="0"/>
<property name="otherCreatorListLimit" value=""/>
<property name="otherCreatorListAbbreviation" value=""/>
<property name="pageFormat" value="2"/>
<property name="editorSwitch" value="1"/>
<property name="editorSwitchIfYes" value="editor (^Ed.^Eds.^). "/>
<property name="primaryCreatorSepFirstBetween" value=", "/>
<property name="primaryCreatorSepNextBetween" value=", "/>
<property name="primaryCreatorSepNextLast" value=", &amp; "/>
<property name="otherCreatorSepFirstBetween" value=", "/>
<property name="otherCreatorSepNextBetween" value=" &amp; "/>
<property name="otherCreatorSepNextLast" value=" &amp; "/>
<property name="primaryTwoCreatorsSep" value=", &amp; "/>
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

<format types="proceedings unpublished">
@?author@@author@. @;author@@?year@(@year@). @;year@@?title@<span style="font-style: italic">@title@</span>. @;title@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@.
</format>

<format types="misc">
@?author@@author@. @;author@@?year@@year@@;year@@?date@, @date@@;date@@?title@<span style="font-style: italic">@title@</span>. @;title@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@@?type@ [@type@]@;type@.
</format>

<format types="book">
@?author@@author@. @;author@@?year@(@year@). @;year@@?title@<span style="font-style: italic">@title@</span>@;title@@?edition@@edition@ ed.@?volume@ @:@@;volume@@;edition@@?volume@Vol. @volume@@;volume@@?address@. @address@: @;address@@?publisher@@?@@:@ @;@@publisher@@;publisher@.
</format>

<format types="inbook">
@?author@@author@. @;author@@?year@(@year@). @;year@@?title@@title@. @;title@@?editor@In @editor@ (@?#editor>1@Eds.@:editor@Ed.@;editor@), @;editor@@?bookitle@@?edition@@:@In @;edition@<span style="font-style: italic">@bookitle@</span> @;bookitle@@?edition@@edition@ ed.@;edition@@?volume@@?pages@, @:@@;pages@Vol. @volume@@;volume@@?pages@@?address@, @:@@;address@pp. @pages@@;pages@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@.
</format>

<format types="article #">
@?author@@author@. @;author@@?year@(@year@). @;year@@?title@@title@. @;title@@?journal@<span style="font-style: italic">@journal@</span>@;journal@@?volume@, @volume@@;volume@@?number@(@number@)@;number@@?pages@, @pages@@;pages@.
</format>

<format types="inproceedings">
@?author@@author@. @;author@@?year@@year@@;year@@?date@, @date@@;date@@?title@<span style="font-style: italic">@title@</span>. @;title@@?booktitle@Paper presented at the @booktitle@@;booktitle@@?address@, @address@@;address@.
</format>

<format types="phdthesis">
@?author@@author@. @;author@@?year@(@year@)@?title@. @:@.@;title@@;year@@?title@<span style="font-style: italic">@title@</span>.@;title@@?type@Unpublished @type@ @;type@@?institution@, @institution@@;institution@@?address@, @address@@;address@.
</format>

<format types="techreport">
@?author@@author@. @;author@@?year@(@year@). @;year@@?title@<span style="font-style: italic">@title@</span> @;title@@?type@@type@@;type@@?number@@?address@ @:@@;address@No. @number@@;number@@?address@@address@: @;address@@?institution@@institution@@;institution@.
</format>

</formats>
