    @{group@
    @?groupkey@<h3 class="papercite">@groupkey@</h3>@;groupkey@
    <ul class="papercite_bibliography">
     @{entry@ <li>
     	@?pdf@ <a href="@pdf@" title='Download PDF' class='papercite_pdf'><img src='@WP_PLUGIN_URL@/papercite/img/pdf.png' alt="[PDF]"/></a>@;pdf@
	@?doi@<a href='http://dx.doi.org/@doi@' class='papercite_doi' title='View document on publisher site'><img src='@WP_PLUGIN_URL@/papercite/img/external.png' width='10' height='10' alt='[DOI]' /></a>@;doi@
    	@#entry@<br/>
	 <a href="javascript:void(0)" id="papercite_@papercite_id@" class="papercite_toggle">[Bibtex]</a>
	 <div class="papercite_bibtex" id="papercite_@papercite_id@_block"><pre><code class="tex bibtex">@bibtex@</code></pre></div>
        </li>
     @}entry@
    </ul>
    @}group@
