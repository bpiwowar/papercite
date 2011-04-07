<formats>

<property name="titleCapitalization" value="0"/>
<property name="primaryCreatorFirstStyle" value="1"/>
<property name="primaryCreatorOtherStyle" value="1"/>
<property name="primaryCreatorInitials" value="0"/>
<property name="primaryCreatorFirstName" value="1"/>
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
<property name="primaryCreatorListAbbreviation" value=", et al."/>
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
<property name="primaryCreatorSepNextLast" value=" &amp; "/>
<property name="otherCreatorSepFirstBetween" value=", "/>
<property name="otherCreatorSepNextBetween" value=", "/>
<property name="otherCreatorSepNextLast" value=" &amp; "/>
<property name="primaryTwoCreatorsSep" value=" &amp; "/>
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

<format types="proceedings unpublished misc">
@?author@@author@ @;author@@?year@(@year@) @;year@@?title@<span style="font-style: italic">@title@</span> @;title@@?editor@@editor@ (@?#editor>1@Eds.@:editor@Ed.@;editor@), @;editor@@?address@@address@, @;address@@?publisher@@publisher@@;publisher@.
</format>

<format types="inproceedings">
@?author@@author@ @;author@@?year@(@year@) @;year@@?title@<span style="font-style: italic">@title@</span> @;title@@?editor@@editor@ (@?#editor>1@Eds.@:editor@Ed.@;editor@), @;editor@@?journal@<span style="font-style: italic">@journal@</span>@;journal@@?address@ @address@, @;address@@?publisher@@publisher@@;publisher@@?pages@, @pages@@;pages@.
</format>

<format types="techreport">
@?author@@author@ @;author@@?year@(@year@) @;year@@?title@<span style="font-style: italic">@title@</span> @;title@@?address@@address@, @;address@@?publisher@@publisher@@;publisher@.
</format>

<format types="book">
@?author@@author@ @;author@@?year@(@year@) @;year@@?title@<span style="font-style: italic">@title@</span> @;title@@?editor@@editor@ (@?#editor>1@Eds.@:editor@Ed.@;editor@), @;editor@@?address@@address@, @;address@@?publisher@@publisher@@;publisher@.
</format>

<format types="inbook">
@?author@@author@ @;author@@?year@(@year@) @;year@@?title@@title@. @;title@@?editor@@editor@ (@?#editor>1@Eds.@:editor@Ed.@;editor@), @;editor@@?bookitle@<span style="font-style: italic">@bookitle@</span>@;bookitle@@?edition@ @edition@ ed.@;edition@@?address@ @address@@;address@.
</format>

<format types="article #">
@?author@@author@ @;author@@?year@(@year@) @;year@@?title@@title@. @;title@@?journal@<span style="font-style: italic">@journal@</span>, @;journal@@?volume@@volume@@;volume@@?pages@@pages@@;pages@.
</format>

<format types="phdthesis">
@?author@@author@ @;author@@?year@(@year@) @;year@@?title@@title@. @;title@@?type@@type@@;type@@?address@@address@, @;address@@?institution@@institution@@;institution@.
</format>

</formats>
