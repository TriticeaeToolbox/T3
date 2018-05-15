function output_file(url) {
        window.open(url);
}
function output_file2(puid) {
    url = "download_metabolite.php?function=downloadMean&pi=" + puid;
    window.open(url);
}

function output_file_plot(puid) {
    url = "download_metabolite.php?function=downloadPlot&trial_code=" + puid;
    window.open(url);
}
