function delay(fn, ms) {
    let timer = 0
    return function (...args) {
        clearTimeout(timer)
        timer = setTimeout(fn.bind(this, ...args), ms || 0)
    }
}
$(function () {
    $('[data-toggle="tooltip"]').tooltip()


    $("#ratingsearch").on('keyup', delay(function (e) {
        $("#searchLoading").show();
        $.get("/api/search.php?q=" + this.value, function (data, status) {
            $("#services").html(data.grid);
            $("#searchLoading").hide();
        });
    }, 500));
});