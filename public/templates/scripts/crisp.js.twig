function delay(fn, ms) {
    var timer = 0;
    return function (args) {
        clearTimeout(timer);
        timer = setTimeout(fn.bind(this, args), ms || 0);
    };
}

$(function () {
    $('[data-toggle="tooltip"]').tooltip();

    $("#ratingsearch").on('keyup', delay(function () {
        $('body').tooltip('dispose');
        $("#searchLoading").show();
        $.get("{{ config.search_url }}?query=" + this.value, function (data) {
            $("#services").html(data.parameters.grid);
            $("#searchLoading").hide();
            $('[data-toggle="tooltip"]').tooltip();
        });
    }, 500));
});

// Backwards compability from old.tosdr.org
if (window.location.hash && window.location.hash !== "#ratings") {
    if (window.location.hash.startsWith("#search=")) {
        window.location.href = "/?search=" + encodeURIComponent(window.location.hash.substr(8));
    } else {

        // https://stackoverflow.com/a/8896309
        var url = window.location.href;
        var exp = url.split(/^(([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?/);
        window.location.href = "/service/" + encodeURIComponent(window.location.hash.substr(1));
    }
}