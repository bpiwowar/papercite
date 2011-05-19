    @{group@
     @{entry@ <div class="papercite_entry">[@key@]
     	@?pdf@ <a href="@pdf@" title='Download PDF' class='papercite_pdf'><img src='@WP_PLUGIN_URL@/papercite/img/pdf.png' alt="[pdf]"/></a>@;pdf@
	@?doi@<a href='http://dx.doi.org/@doi@' class='papercite_doi' title='Go to document'><img src='@WP_PLUGIN_URL@/papercite/img/external.png' width='10' height='10' alt='[doi]' /></a>@;doi@
    	@#entry@<br/>
	 <a href="javascript:void(0)" id="papercite_@papercite_id@" class="papercite_toggle">[Bibtex]</a></div>
         <pre class="papercite_bibtex" id="papercite_@papercite_id@_block"><code>@bibtex@</code></pre>
     @}entry@
    @}group@