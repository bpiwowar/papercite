    @{group@
     @{entry@ <div>[@key@]
     	@?pdf@ <a href="@pdf@"><img src='@WP_PLUGIN_URL@/papercite/img/pdf.png'/></a>@;pdf@
	@?doi@<a href='http://dx.doi.org/@doi@' title='Go to document'><img src='@WP_PLUGIN_URL@/papercite/img/external.png' width='10' height='10' alt='Go to document' /></a>@;doi@
    	@#entry@
	 <div><a href="javascript:void(0)" ref="papercite_@papercite_id@" class="papercite_toggle">show bibtex</a> <div class="papercite_bibtex" id="papercite_@papercite_id@">
         @bibtex@</div></div>
        </div>
     @}entry@
    @}group@
  </body>
