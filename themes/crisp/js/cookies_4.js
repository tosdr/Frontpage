/*
 * Since the servers are hosted in germany, this is necessary. Sorry folks!
 */
function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}
function getCookie(name) {
    var cookieName = name + "=";
    var docCookie = document.cookie;
    var cookieStart;
    var end;

    if (docCookie.length > 0) {
        cookieStart = docCookie.indexOf(cookieName);
        if (cookieStart != -1) {
            cookieStart = cookieStart + cookieName.length;
            end = docCookie.indexOf(";", cookieStart);
            if (end == -1) {
                end = docCookie.length;
            }
            return unescape(docCookie.substring(cookieStart, end));
        }
    }
    return false;
}

function eraseCookie(name) {
    document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function cookieConsent() {
    if (!getCookie('allowCookies')) {
        $('.toast').toast('show');
    } else {
        $('.toast').remove();
    }
}
$('#btnDeny').click(() => {
    $('.toast').remove();
});

$('#btnAccept').click(() => {
    setCookie('allowCookies', '1', 7);
    $('.toast').remove();
});

cookieConsent();