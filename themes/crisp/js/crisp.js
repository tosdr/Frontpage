/*jslint browser: true*/
/*global $, jQuery, alert*/

'use strict';

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
        $.get("/api/search/" + this.value, function (data) {
            $("#services").html(data.parameters.grid);
            $("#searchLoading").hide();
            $('[data-toggle="tooltip"]').tooltip();
        });
    }, 500));
});