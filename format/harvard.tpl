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
@?author@@author@ @;@@?year@(@year@) @;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt; @;@@?editor@@editor@ (@?#editor&gt;1@Eds.@:editor@Ed.@;editor@), @;@@?address@@address@, @;@@?publisher@@publisher@@;@.
</format>

<format types="inproceedings">
@?author@@author@ @;@@?year@(@year@) @;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt; @;@@?editor||journal@IN @:@@;@@?editor@@editor@ (@?#editor&gt;1@Eds.@:editor@Ed.@;editor@), @;@@?journal@&lt;span style=&quot;font-style: italic&quot;&gt;@journal@&lt;/span&gt;@;@@?editor||journal@.@:@@;@@?address@ @address@, @;@@?publisher@@publisher@@;@@?pages@, @pages@@;@.
</format>

<format types="techreport">
@?author@@author@ @;@@?year@(@year@) @;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt; @;@@?address@@address@, @;@@?publisher@@publisher@@;@.
</format>

<format types="book">
@?author@@author@ @;@@?year@(@year@) @;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt; @;@@?editor@@editor@ (@?#editor&gt;1@Eds.@:editor@Ed.@;editor@), @;@@?address@@address@, @;@@?publisher@@publisher@@;@.
</format>

<format types="inbook incollection">
@?author@@author@ @;@@?year@(@year@) @;@@?title@@title@. @;@@?editor||booktitle@IN @:@@;@@?editor@@editor@ (@?#editor&gt;1@Eds.@:editor@Ed.@;editor@), @;@@?booktitle@&lt;span style=&quot;font-style: italic&quot;&gt;@booktitle@&lt;/span&gt;@;@@?editor||booktitle@.@:@@;@@?edition@ @edition@ ed.@;@@?address@ @address@@;@.
</format>

<format types="article #">
@?author@@author@ @;@@?year@(@year@) @;@@?title@@title@. @;@@?journal||volume@IN @:@@;@@?journal@&lt;span style=&quot;font-style: italic&quot;&gt;@journal@&lt;/span&gt;, @;@@?volume@@volume@@;@@?journal||volume@.@:@@;@@?pages@@pages@@;@.
</format>

<format types="phdthesis mastersthesis">
@?author@@author@ @;@@?year@(@year@) @;@@?title@@title@. @;@@?type@@type@@;@@?entrytype=phdthesis||entrytype=mastersthesis@ @?entrytype=phdthesis@PhD Thesis@;@@?entrytype=mastersthesis@Master Thesis@;@. @;@@?address@@address@, @;@@?institution@@institution@@;@.
</format>

</formats>
