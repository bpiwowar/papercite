<formats>

<property name="titleCapitalization" value="0"/>
<property name="primaryCreatorFirstStyle" value="1"/>
<property name="primaryCreatorOtherStyle" value="0"/>
<property name="primaryCreatorInitials" value="0"/>
<property name="primaryCreatorFirstName" value="0"/>
<property name="otherCreatorFirstStyle" value="0"/>
<property name="otherCreatorOtherStyle" value="0"/>
<property name="otherCreatorInitials" value="0"/>
<property name="dayFormat" value="0"/>
<property name="otherCreatorFirstName" value="0"/>
<property name="primaryCreatorList" value="1"/>
<property name="otherCreatorList" value="1"/>
<property name="monthFormat" value="0"/>
<property name="editionFormat" value="1"/>
<property name="primaryCreatorListMore" value="4"/>
<property name="primaryCreatorListLimit" value="1"/>
<property name="dateFormat" value="0"/>
<property name="primaryCreatorListAbbreviation" value=", et al."/>
<property name="otherCreatorListMore" value="4"/>
<property name="runningTimeFormat" value="0"/>
<property name="primaryCreatorRepeatString" value="---"/>
<property name="primaryCreatorRepeat" value="2"/>
<property name="otherCreatorListLimit" value="1"/>
<property name="otherCreatorListAbbreviation" value=", et al."/>
<property name="pageFormat" value="2"/>
<property name="editorSwitch" value="1"/>
<property name="editorSwitchIfYes" value="editor, ^ed.^eds.^ "/>
<property name="primaryCreatorSepFirstBetween" value=", "/>
<property name="primaryCreatorSepNextBetween" value=", "/>
<property name="primaryCreatorSepNextLast" value=", and "/>
<property name="otherCreatorSepFirstBetween" value=", "/>
<property name="otherCreatorSepNextBetween" value=", "/>
<property name="otherCreatorSepNextLast" value=" and "/>
<property name="primaryTwoCreatorsSep" value=" and "/>
<property name="otherTwoCreatorsSep" value=" and "/>
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
@?author@@author@. @;@@?title@@title@. @;@@?editor@@?#editor&gt;1@Eds.@:editor@Ed.@;editor@ @editor@. @;@@?address@@address@@?publisher@: @:@@;publisher@@;@@?publisher@@publisher@, @;@@?year@@year@@;@.
</format>

<format types="inproceedings">
@?author@@author@. @;@@?title@&quot;@title@.&quot; @;@@?journal@@journal@. @;@@?editor@@?#editor&gt;1@Eds.@:editor@Ed.@;editor@ @editor@. @;@@?address@@address@: @;@@?publisher@@publisher@, @;@@?date@@date@ @;@@?year@@year@. @;@@?pages@@pages@@;@.
</format>

<format types="book">
@?author@@author@. @;@@?title@@title@. @;@@?series@@series@. @;@@?editor@@?#editor&gt;1@Eds.@:editor@Ed.@;editor@ @editor@. @;@@?edition@@edition@ ed. @;@@?volume@Vol. @volume@. @;@@?address@@address@@?publisher@: @:@@;publisher@@;@@?publisher@@publisher@, @;@@?year@@year@@;@.
</format>

<format types="inbook incollection">
@?author@@author@. @;@@?title@&quot;@title@.&quot; @;@@?booktitle@@booktitle@. @;@@?editor@@?#editor&gt;1@Eds.@:editor@Ed.@;editor@ @editor@. @;@@?edition@@edition@ ed. @;@@?volume@Vol. @volume@. @;@@?series@@series@. @;@@?address@@address@: @;@@?publisher@@publisher@, @;@@?year@@year@. @;@@?pages@@pages@@;@.
</format>

<format types="article #">
@?author@@author@. @;@@?title@&quot;@title@.&quot; @;@@?journal@&lt;span style=&quot;font-weight: bold&quot;&gt;@journal@&lt;/span&gt; @;@@?volume@@volume@@?number@.@:@ @;number@@;@@?number@@number@ @;@@?year@(@year@)@?pages@: @:@@;pages@@;@@?pages@@pages@@;@.
</format>

<format types="phdthesis mastersthesis">
@?author@@author@. @;@@?title@&quot;@title@.&quot; @;@@?type@@type@ @;@@?entrytype=phdthesis||entrytype=mastersthesis@@?entrytype=phdthesis@PhD Thesis@;@@?entrytype=mastersthesis@Master Thesis@;@. @;@@?institution@@institution@, @;@@?year@@year@@;@.
</format>

<format types="techreport">
@?author@@author@. @;@@?institution@@institution@, @;@@?title@&lt;span style=&quot;font-weight: bold&quot;&gt;@title@&lt;/span&gt;. @;@@?year@@year@@;@@?address@ @address@@;@.
</format>

</formats>
