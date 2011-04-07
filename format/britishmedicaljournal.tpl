<formats>

<property name="titleCapitalization" value="0"/>
<property name="primaryCreatorFirstStyle" value="2"/>
<property name="primaryCreatorOtherStyle" value="2"/>
<property name="primaryCreatorInitials" value="3"/>
<property name="primaryCreatorFirstName" value="0"/>
<property name="otherCreatorFirstStyle" value="2"/>
<property name="otherCreatorOtherStyle" value="2"/>
<property name="otherCreatorInitials" value="3"/>
<property name="dayFormat" value="0"/>
<property name="otherCreatorFirstName" value="0"/>
<property name="primaryCreatorList" value="0"/>
<property name="otherCreatorList" value="1"/>
<property name="monthFormat" value="1"/>
<property name="editionFormat" value="1"/>
<property name="primaryCreatorListMore" value="7"/>
<property name="primaryCreatorListLimit" value="6"/>
<property name="dateFormat" value="1"/>
<property name="primaryCreatorListAbbreviation" value=", et al."/>
<property name="otherCreatorListMore" value="7"/>
<property name="runningTimeFormat" value="1"/>
<property name="primaryCreatorRepeatString" value=""/>
<property name="primaryCreatorRepeat" value="0"/>
<property name="otherCreatorListLimit" value="6"/>
<property name="otherCreatorListAbbreviation" value=", et al."/>
<property name="pageFormat" value="2"/>
<property name="editorSwitch" value="1"/>
<property name="editorSwitchIfYes" value="editor, ^`editor.`^`editors.`^ "/>
<property name="primaryCreatorSepFirstBetween" value=", "/>
<property name="primaryCreatorSepNextBetween" value=", "/>
<property name="primaryCreatorSepNextLast" value=", "/>
<property name="otherCreatorSepFirstBetween" value=", "/>
<property name="otherCreatorSepNextBetween" value=", "/>
<property name="otherCreatorSepNextLast" value=", "/>
<property name="primaryTwoCreatorsSep" value=", "/>
<property name="otherTwoCreatorsSep" value=", "/>
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
@?author@@author@. @;author@@?title@<span style="font-style: italic">@title@</span>. @;title@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@@?year@, @year@@;year@.
</format>

<format types="techreport">
@?author@@author@. @;author@@?title@<span style="font-style: italic">@title@</span>. @;title@@?type@[@type@] @;type@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@@?year@@?date@, @:@@;date@@year@@;year@@?date@ @date@@;date@.
</format>

<format types="book">
@?author@@author@. @;author@@?title@<span style="font-style: italic">@title@</span>. @;title@@?edition@@edition@ ed. @;edition@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@@?year@@?@, @:@@;@@year@@;year@.
</format>

<format types="inbook">
@?author@@author@. @;author@@?title@@title@. @;title@@?editor@In: @editor@, @?#editor>1@`editors`@:editor@`editor`@;editor@. @;editor@@?bookitle@@?edition@@:@In: @;edition@<span style="font-style: italic">@bookitle@</span>. @;bookitle@@?edition@@edition@ ed. @;edition@@?address@@address@: @;address@@?publisher@@publisher@@;publisher@@?year@@?pages@, @:@@;pages@@year@@;year@@?pages@@?@:@:@, @;@@pages@@;pages@.
</format>

<format types="article #">
@?author@@author@. @;author@@?title@@title@. @;title@@?journal@<span style="font-style: italic">@journal@</span> @;journal@@?year@@year@@;year@@?volume@;@volume@@;volume@@?number@(@number@)@;number@@?pages@:@pages@@;pages@.
</format>

<format types="inproceedings">
@?author@@author@. @;author@@?title@@title@. @;title@@?booktitle@@booktitle@: @;booktitle@@?year@@year@ @;year@@?date@@date@; @;date@@?address@@address@. @;address@@?organization@@organization@@;organization@.
</format>

<format types="phdthesis">
@?author@@author@. @;author@@?title@@title@ @;title@@?type@@type@@;type@@?institution@. @institution@@;institution@@?year@, @year@@;year@.
</format>

</formats>
