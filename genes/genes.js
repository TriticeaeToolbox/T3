/* jshint browser: true */

var php_self = document.location.href;
var title = document.title;

function display() {
   assembly = $('input[name="assembly"]:checked').val();
   jQuery.ajax({
      type: "GET",
      url: php_self,
      data: "function=displayGenes&assembly=" + assembly,
      success: function(data, textStatus) {
        jQuery("#step2").html(data);
        document.title = title;
      },
      error: function() {
        alert("Error finding asembly");
      }
    });
}

function nextPage(page) {
   assembly = $('input[name="assembly"]:checked').val();
   jQuery.ajax({
      type: "GET",
      url: php_self,
      data: "function=displayGenes&assembly=" + assembly + "&page=" + page,
      success: function(data, textStatus) {
        jQuery("#step2").html(data);
        document.title = title;
      },
      error: function() {
        alert("Error finding asembly");
      }
    });
}

$(document).ready(function(){
    $("input[type=radio]").click(function(){
        display();
    });
});
