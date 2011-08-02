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
@?author@@author@. @;@@?year@(@year@). @;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt;. @;@@?address@@address@: @;@@?publisher@@publisher@@;@.
</format>

<format types="misc">
@?author@@author@. @;@@?year||date@(@:@@;@@?year@@year@@;@@?date@, @date@@;@@?year||date@). @:@@;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt;. @;@@?address@@address@: @;@@?publisher@@publisher@@;@@?type@ [@type@]@;@.
</format>

<format types="book">
@?author@@author@. @;@@?year@(@year@). @;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt;@;@@?edition||volume@ (@:@@;@@?edition@@edition@ ed.@?volume@ @:@@;volume@@;@@?volume@Vol. @volume@@;@@?edition||volume@)@:@@;@@?address@. @address@: @;@@?publisher@@?@@:@ @;@@publisher@@;@.
</format>

<format types="inbook incollection">
@?author@@author@. @;@@?year@(@year@). @;@@?title@@title@. @;@@?editor@In @editor@ (@?#editor&gt;1@Eds.@:editor@Ed.@;editor@), @;@@?booktitle@@?edition@@:@In @;edition@&lt;span style=&quot;font-style: italic&quot;&gt;@booktitle@&lt;/span&gt; @;@@?edition||volume||pages@(@:@@;@@?edition@@edition@ ed.@;@@?volume@@?pages@, @:@@;pages@Vol. @volume@@;@@?pages@@?address@, @:@@;address@pp. @pages@@;@@?edition||volume||pages@). @:@@;@@?address@@address@: @;@@?publisher@@publisher@@;@.
</format>

<format types="article #">
@?author@@author@. @;@@?year@(@year@). @;@@?title@@title@. @;@@?journal@&lt;span style=&quot;font-style: italic&quot;&gt;@journal@&lt;/span&gt;@;@@?volume@, @volume@@;@@?number@(@number@)@;@@?pages@, @pages@@;@.
</format>

<format types="inproceedings">
@?author@@author@. @;@@?year||date@(@:@@;@@?year@@year@@;@@?date@, @date@@;@@?year||date@). @:@@;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt;. @;@@?booktitle@Paper presented at the @booktitle@@;@@?address@, @address@@;@.
</format>

<format types="phdthesis mastersthesis">
@?author@@author@. @;@@?year||title@(@:@@;@@?year@(@year@)@?title@. @:@.@;title@@;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt;.@;@@?year||title@). @:@@;@@?type@Unpublished @type@ @;@@?entrytype=phdthesis||entrytype=mastersthesis@@?entrytype=phdthesis@PhD Thesis@;@@?entrytype=mastersthesis@Master Thesis@;@@;@@?institution@, @institution@@;@@?address@, @address@@;@.
</format>

<format types="techreport">
@?author@@author@. @;@@?year@(@year@). @;@@?title@&lt;span style=&quot;font-style: italic&quot;&gt;@title@&lt;/span&gt; @;@@?type||number@(@:@@;@@?type@@type@@;@@?number@@?address@ @:@@;address@No. @number@@;@@?type||number@). @:@@;@@?address@@address@: @;@@?institution@@institution@@;@.
</format>

</formats>
