/* jshint browser: true */

var php_self = document.location.href;
var title = document.title;

function displayQuery()
{
    jQuery.ajax({
        type: "GET",
        url: php_self,
        data: "function=displayQuery",
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
   jQuery.ajax({
      type: "GET",
      url: php_self,
      data: "function=displayCompounds",
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
   jQuery.ajax({
      type: "GET",
      url: php_self,
      data: "function=displayCompounds&page=" + page,
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
