    @{group@
    @?groupkey@<h3 class="papercite">@groupkey@</h3>@;groupkey@
    <ul class="papercite_bibliography">
     @{entry@ <li>
     	@?pdf@ <a href="@pdf@" title='Download PDF' class='papercite_pdf' alt='[pdf]' ><img src='@WP_PLUGIN_URL@/papercite/img/pdf.png' alt="[PDF]"/></a>@;pdf@
	@?doi@<a href='http://dx.doi.org/@doi@' class='papercite_doi' alt='[doi]' title='Go to document'><img src='@WP_PLUGIN_URL@/papercite/img/external.png' width='10' height='10' alt='Go to document' /></a>@;doi@
    	@#entry@<br/>
	 <a href="javascript:void(0)" id="papercite_@papercite_id@" class="papercite_toggle">[Bibtex]</a>
	 <pre class="papercite_bibtex" id="papercite_@papercite_id@_block"><code>@bibtex@</code></pre>
        </li>
     @}entry@
    </ul>
    @}group@
