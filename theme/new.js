/*global $,Ajax,window,document,setTimeout*/

var title = document.title;

function getElmt(id)
{
    if (document.getElementById) { //DOM
        return (document.getElementById(id));
    } else if (document.all) { //IE
        return (document.all[id]);
    }
}

function moveQuickLinks()
{
    var quickLinks = getElmt("quicklinks");
    var pos = 0;
    if (document.documentElement.scrollTopMax) {
        pos = 15 + document.documentElement.scrollTop; // Firefox
    } else {
        pos = 15 + document.body.scrollTop; // Chrome, Safari, IE
    }
    if (pos < 141) {
        pos = 141;
    }
    quickLinks.style.top = pos + "px";
    setTimeout(moveQuickLinks, 0);
}
if (screen.width >= 640) {
    setTimeout(moveQuickLinks, 2000);
}

function set_over()
{
    this.className = "over";
}

function set_blank()
{
    this.className = '';
}

var startList = function () {
    if (document.all && document.getElementById) {
        var navRoot = document.getElementById("nav"), i, node;
        for (i = 0; i < navRoot.childNodes.length; i++) {
            node = navRoot.childNodes[i];
            if (node.nodeName === "LI") {
                node.onmouseover = set_over();
                node.onmouseout = set_blank();
            }
        }
    }
};

function update_side_menu()
{
    jQuery.ajax({
        url: "side_menu.php",
        success: function (data, textSataus) {
            jQuery("#quicklinks").html(data);
            $('quicklinks').show();
            document.title = title;
        },
        error: function () {
            alert("Error in side menu");
        }
    });
}

window.onload = startList;
