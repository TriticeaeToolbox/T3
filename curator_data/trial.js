var php_self = document.location.href;

function update_database(filepath, filename, username, data_public_flag)
{
    var url= php_self + "?function=typeDatabase&linedata=" + filepath + "&file_name=" + filename + "&user_name=" + username + "&public=" + data_public_flag;
        // Opens the url in the same window
        window.open(url, "_self");
}
