(function() {
    
    function domReady(fn) {
      // If we're early to the party
      document.addEventListener("DOMContentLoaded", fn);
      // If late; I mean on time.
      if (document.readyState === "interactive" || document.readyState === "complete" ) {
        fn();
      }
    }
    
    // Show an element
    var show = function (elem) {
    	elem.style.display = 'block';
    };
    
    // Hide an element
    var hide = function (elem) {
    	elem.style.display = 'none';
    };
    
    // Toggle element visibility
    var toggle = function (elem) {
    
    	// If the element is visible, hide it
    	if (window.getComputedStyle(elem).display === 'block') {
    		hide(elem);
    		return;
    	}
    
    	// Otherwise, show it
    	show(elem);
    
    };
    
    
    domReady(() => 
    {
        var userSelection = document.getElementsByClassName('papercite_toggle');
    
        // Toggle Single Bibtex entry
        for(var i = 0; i < userSelection.length; i++) {
            userSelection[i].addEventListener("click", function() {
               toggle(document.querySelector( "#" + this.getAttribute("id") + "_block" ));
               return false;
             });
        }
    });

})();
