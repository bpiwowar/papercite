    @{group@
    <h3>@groupkey@ (@groupcount@)</h3>

    	@#entry@
    <ul>
      @{entry@
      <div>
            <span class="papercite_author">@author@</span>. <span class="publist_title">@title@</span>.  @?journal@<span class="publist_rest">@journal@@?volume@ @volume@@?number@ (@number@)@;number@@;volume@</span>, @;journal@            @?publisher@<span class="publist_rest">@publisher@</span>, @;publisher@             @?address@<span class="publist_rest">@address@</span>, @;address@            <span class="publist_date">@?year@@?month@@month@ @;month@@year@@;year@</span>
	    <a href="javascript:void(0)" ref="papercite_@entryid@" class="papercite_toggle">show bibtex</a> <div class="papercite_bibtex" id="papercite_@entryid@">
         @bibtex@
        </div>
      </div>
      @}entry@
    </ul>

    @}group@
  </body>
