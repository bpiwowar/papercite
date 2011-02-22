

var $j = jQuery.noConflict();

$j(document).ready(function() {
    // Toggle Single Bibtex entry
    $j('a.papercite_toggle').click(function() {
		$j( "#" + $j(this).attr("ref") ).toggle();
		return false;
    });
});

