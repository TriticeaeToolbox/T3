/* jshint browser: true */

var php_self = document.location.href;
var title = document.title;

function displayQuery()
{
    assembly = $('input[name="assembly"]:checked').val();
    jQuery.ajax({
        type: "GET",
        url: php_self,
        data: "function=displayQuery&assembly=" + assembly,
        success: function (data, textStatus) {
            jQuery("#step1").html(data);
            document.title = title;
        },
        error: function () {
            alert("Error finding assembly");
        }
    });
}

function display()
{
   assembly = $('input[name="assembly"]:checked').val();
   jQuery.ajax({
      type: "GET",
      url: php_self,
      data: "function=displayGenes&assembly=" + assembly,
      success: function (data, textStatus) {
        jQuery("#step2").html(data);
        document.title = title;
      },
      error: function () {
        alert("Error finding gene");
      }
    });
}

function nextPage(page)
{
   assembly = $('input[name="assembly"]:checked').val();
   jQuery.ajax({
      type: "GET",
      url: php_self,
      data: "function=displayGenes&assembly=" + assembly + "&page=" + page,
      success: function (data, textStatus) {
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
        displayQuery();
        display();
    });
});
