var selectBy = "";

function step2Location()
{
    var items = [];
    var unq = {};
    var selLocation = document.getElementById("selLocation").value;
    var apiUrl = document.getElementById("url").value + "/studies-search?studyType=Phenotype&locationDbId=" + selLocation;
    if (document.getElementById("YesDebug").checked === true) {
      items.push("API call = " + apiUrl);
    }

    items.push("<select><option>Years</option></select><br>");
    jQuery.ajax({
    type: "GET",
    dataType: "json",
    url: apiUrl,
    success: function(data, textStatus) {
      items.push("<select multiple=\"multiple\">");
      jQuery.each( data, function( key, val ) {
        if (key == "metadata") {
        } else if (key == "result") {
          jQuery.each( val, function( key2, val2 ) {
            if ((key2 == "data") && (typeof val2 == "object")) {
              jQuery.each( val2, function (key3, val3) {
                if (typeof val3 == "object") {
                  items.push("<tr>");
                  jQuery.each( val3, function(key4, val4) {
                    if (key4 == "seasons") {
                      if (!unq.hasOwnProperty(val4[0])) {
                        unq[val4[0]] = 1;
                        items.push("<option>" + val4[0] + "</option>");
                      }
                    }
                  });
                }
              });
            }
          });
        }
      });
      items.push("</select>");
      var html = items.join("");
      jQuery("#step2").html(html);
  },
  error: function() {
        alert('Error in selecting experiment list');
      }
  });

}

function step2Studies(parm1)
{
    var items = [];
    if (parm1 == "selTrial") {
      var selTrial = document.getElementById("selTrial").value;
      var apiUrl = document.getElementById("url").value + "/studies-search?studyType=Phenotype&trialDbId=" + selTrial;
    } else if (parm1 == "selProg") {
      var selProg = document.getElementById("selProg").value;
      var apiUrl = document.getElementById("url").value + "/studies-search?studyType=Phenotype&programDbId=" + selProg;
    }
    if (document.getElementById("YesDebug").checked === true) {
      items.push("API call = " + apiUrl);
    }

    items.push("<select><option>Studies</option></select><br>");

  jQuery.ajax({
    type: "GET",
    dataType: "json",
    url: apiUrl,
    success: function(data, textStatus) {
      items.push("<select multiple=\"multiple\">");
      jQuery.each( data, function( key, val ) {
        if (key == "metadata") {
        } else if (key == "result") {
          jQuery.each( val, function( key2, val2 ) {
            if ((key2 == "data") && (typeof val2 == "object")) {
              jQuery.each( val2, function (key3, val3) {
                if (typeof val3 == "object") {
                  items.push("<tr>");
                  jQuery.each( val3, function(key4, val4) {
                    if (key4 == "name") {
                      items.push("<option>" + val4 + "</option>");
                    }
                  });
                }
              });
            }
          });
        }
      });
      items.push("</select>");    
      var html = items.join("");
      jQuery("#step2").html(html);
  },
  error: function() {
        alert('Error in selecting experiment list');
      }
  });
}

function getListLocations()
{
  var items = [];
  var name = "";
  var apiUrl = document.getElementById("url").value + "/locations";
  if (document.getElementById("YesDebug").checked === true) {
      items.push("API call = " + apiUrl);
  }
  jQuery.ajax({
    type: "GET",
    dataType: "json",
    url: apiUrl,
    success: function(data, textStatus) {
      items.push("<select id=\"selLocation\" multiple=\"multiple\" onclick=\"step2Location()\">");
      jQuery.each( data, function( key, val ) {
        if (key == "metadata") {
        } else if (key == "result") {
          jQuery.each( val, function( key2, val2 ) {
            if ((key2 == "data") && (typeof val2 == "object")) {
              jQuery.each( val2, function (key3, val3) {
                if (typeof val3 == "object") {
                  items.push("<tr>");
                  jQuery.each( val3, function(key4, val4) {
                    if (key4 == "name") {
                      name = val4;
                    } else if (key4 == "locationDbId") {
                      locationDbId = val4;
                    }
                  });
                  items.push("<option value=\"" + locationDbId + "\">" + name + "</option>");
                 }
              });
            }
          });
        }
      });
      items.push("</select>");
      var html = items.join("");
      jQuery("#step12").html(html);
    },
    error: function() {
        alert('Error in selecting experiment list');
      }
  });
}

function getListTrials()
{
  var items = [];
  var studyId = "";
  var apiUrl = document.getElementById("url").value + "/trials";
  if (document.getElementById("YesDebug").checked === true) {
      items.push("API call = " + apiUrl);
  }
  jQuery.ajax({
    type: "GET",
    dataType: "json",
    url: apiUrl,
    success: function(data, textStatus) {
      items.push("<select id=\"selTrial\" multiple=\"multiple\" onclick=\"step2Studies('selTrial')\">");
      jQuery.each( data, function( key, val ) {
        if (key == "metadata") {
        } else if (key == "result") {
          jQuery.each( val, function( key2, val2 ) {
            if ((key2 == "data") && (typeof val2 == "object")) {
              jQuery.each( val2, function (key3, val3) {
                if (typeof val3 == "object") {
                  items.push("<tr>");
                  jQuery.each( val3, function(key4, val4) {
                    if (key4 == "trialName") {
                      trialName = val4;
                    } else if (key4 == "trialDbId") {
                      trialDbId = val4;
                    }
                  });
                  items.push("<option value=\"" + trialDbId + "\">" + trialName + "</option>");
                }
              });
            }
          });
        }
      });
      items.push("</select>");
      var html = items.join("");
      jQuery("#step12").html(html);
      //step2Studies();
    },
    error: function() {
        alert('Error in selecting experiment list');
      }
  });
}

function getListStudies()
{
  var items = [];
  var studyId = "";
  var apiUrl = document.getElementById("url").value + "/studies-search?studyType=Phenotype";
  if (document.getElementById("YesDebug").checked === true) {
      items.push("API call = " + apiUrl);
  }
  jQuery.ajax({
    type: "GET",
    dataType: "json",
    url: apiUrl,
    success: function(data, textStatus) {
      items.push("<select multiple=\"multiple\">");
      jQuery.each( data, function( key, val ) {
        if (key == "metadata") {
        } else if (key == "result") {
          jQuery.each( val, function( key2, val2 ) {
            if ((key2 == "data") && (typeof val2 == "object")) {
              jQuery.each( val2, function (key3, val3) {
                if (typeof val3 == "object") {
                  items.push("<tr>");
                  jQuery.each( val3, function(key4, val4) {
                    if (key4 == "name") {
                      items.push("<option>" + val4 + "</option>");
                    }
                  });
                }
              });
            }
          });
        }
      });
      items.push("</select>");
      var html = items.join("");
      jQuery("#step12").html(html);
      jQuery("#step2").html("");
    },
    error: function() {
        alert('Error in selecting experiment list');
      }
  });
}

function getListTraits()
{
  var items = [];
  var studyId = "";
  var apiUrl = document.getElementById("url").value + "/traits";
  if (document.getElementById("YesDebug").checked === true) {
      items.push("API call = " + apiUrl);
  }
  jQuery.ajax({
    type: "GET",
    dataType: "json",
    url: apiUrl,
    success: function(data, textStatus) {
      items.push("<select multiple=\"multiple\">");
      jQuery.each( data, function( key, val ) {
        if (key == "metadata") {
        } else if (key == "result") {
          jQuery.each( val, function( key2, val2 ) {
            if ((key2 == "data") && (typeof val2 == "object")) {
              jQuery.each( val2, function (key3, val3) {
                if (typeof val3 == "object") {
                  items.push("<tr>");
                  jQuery.each( val3, function(key4, val4) {
                    if (key4 == "name") {
                      items.push("<option>" + val4 + "</option>");
                    }
                  });
                }
              });
            }
          });
        }
      });
      items.push("</select>");
      var html = items.join("");
      jQuery("#step12").html(html);
      jQuery("#step2").html("");
    },
    error: function() {
        alert('Error in selecting experiment list');
      }
  });
}

function getListPrograms()
{
  var unq = {};
  var unqItems = [];
  var items = [];
  var programDbId = "";
  var programName = "";
  var programList = [];
  var apiUrl = document.getElementById("url").value + "/studies-search?studyType=Phenotype";
  if (document.getElementById("YesDebug").checked === true) {
      items.push("API call = " + apiUrl);
  }   
  jQuery.ajax({
    type: "GET",
    dataType: "json",
    url: apiUrl,
    success: function(data, textStatus) {
      items.push("<select id=\"selProg\" multiple=\"multiple\" onclick=\"step2Studies('selProg')\">");
      jQuery.each( data, function( key, val ) {
        if (key == "metadata") {
        } else if (key == "result") {
          jQuery.each( val, function( key2, val2 ) {
            if ((key2 == "data") && (typeof val2 == "object")) {
              jQuery.each( val2, function (key3, val3) {
                if (typeof val3 == "object") {
                  items.push("<tr>");
                  jQuery.each( val3, function(key4, val4) {
                    if (key4 == "programDbId") {
                      programDbId = val4;
                    }
                    if (key4 == "programName") {
                      programName = val4;
                      if (!unq.hasOwnProperty(val4)) {
                        unq[val4] = 1;
                        unqItems.push(val4);
                        //items.push("<option>" + val4 + "</option>");
                      }
                    } 
                  });
                  programList[programName] = programDbId;
                } 
              });
            } 
          });
        } 
      });
      unqItems.sort();
      for (var i = 0, len = unqItems.length; i < len; i++) {
          programName = unqItems[i];
          programDbId = programList[programName];
          items.push("<option value=" + programDbId + ">" + unqItems[i] + "</option>");
      }
      items.push("</select>");
      var html = items.join("");
      jQuery("#step12").html(html);
      jQuery("#step2").html("");
    },
    error: function() {
        alert('Error in selecting program');
      } 
  }); 
}

function updateUrl()
{
    var apiUrlList = document.getElementById("url2").value;
    document.getElementById("url").value = apiUrlList;
}

function updateStep1()
{
    var selectBy = document.getElementById("step11").value;
    if (selectBy == "Location") {
        getListLocations();
    } else if (selectBy == "Study") {
        getListStudies();
    } else if (selectBy == "Trial") {
       getListTrials();
    } else if (selectBy == "Trait") {
       getListTraits();
    } else if (selectBy == "Program") {
       getListPrograms();
    }
}

window.onload = getListPrograms;
