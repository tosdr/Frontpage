$('[data-alert-toggle="close"]').on("click", function () {
    let alertelement = $($(this).data("alert-target"));
    alertelement.remove();
    var now = new Date();
    now.setMonth(now.getMonth() + 1);
    let year = now.toUTCString();
    document.cookie =
        "Dismissed-" +
        alertelement.attr("id") +
        "=true; expires=" +
        year +
        "; path=/";
});
