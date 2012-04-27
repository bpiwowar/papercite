@{group@
@?groupkey@
<h3 class="papercite">@groupkey@</h3>
@;groupkey@
<ul class="papercite_bibliography">
@{entry@
<li>
	@#entry@
	@?doi@
	<a href='http://dx.doi.org/@doi@' class='papercite_doi' title='View on publisher site'>doi:@doi@</a>
	@;doi@
	<br/>
	<a href="javascript:void(0)" id="papercite_@papercite_id@" class="papercite_toggle">[BibTeX]</a>
	@?abstract@
	<a href="javascript:void(0)" id="papercite_abstract_@papercite_id@" class="papercite_toggle">[Abstract]</a>
	@;abstract@
	@?url@
	<a href="@url@" title='Download PDF' class='papercite_pdf'>[Download PDF]</a>
	@;url@
	@?abstract@
	<blockquote class="papercite_bibtex" id="papercite_abstract_@papercite_id@_block">@abstract@</blockquote>
	@;abstract@
	<div class="papercite_bibtex" id="papercite_@papercite_id@_block">
		<pre><code class="tex bibtex">@bibtex@</code></pre>
	</div>
</li>
@}entry@
</ul>
@}group@
