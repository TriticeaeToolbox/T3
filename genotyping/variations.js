var php_self = document.location.href;

function display()
{
   assembly = $('input[name="assembly"]:checked').val();
   jQuery.ajax({
      type: "GET",
      url: php_self,
      data: "function=displayVariations&assembly=" + assembly,
      success: function (data, textStatus) {
        jQuery("#step2").html(data);
        document.title = title;
      },
      error: function () {
        alert("Error finding gene");
      }
    });
}

$(document).ready(function(){
    $("input[type=radio]").click(function(){
        display();
    });
});
