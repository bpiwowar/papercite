    @{group@
    <h3>@groupkey@ (@groupcount@)</h3>
    <ul>
     @{entry@ <li>
     	@?pdf@ <a href="@pdf@"><img src='@WP_PLUGIN_URL@/papercite/pdf.png'/></a>@;pdf@
    	@#entry@
	 <div><a href="javascript:void(0)" ref="papercite_@entryid@" class="papercite_toggle">show bibtex</a> <div class="papercite_bibtex" id="papercite_@entryid@">
         @bibtex@</div></div>
        </li>
     @}entry@
    </ul>
    @}group@
  </body>
