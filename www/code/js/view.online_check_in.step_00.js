/************************************************************************
 * @author	Kingsley U. Uwode II, contractor via Robert Half Technologies
 * @since	July 13 2015
 * @link	mailto:kingzmeadow@hotmail.com
 */
// Added by Kevin

// Check logout action
document.onreadystatechange = function () {
    if (document.readyState === 'complete') {

        var current_url = window.location.href;
        if (get_url_param('logout', current_url) !== null) {
            document.getElementById('session_expired').style.display = 'none';
            document.getElementById('session_logout').style.display = 'block';
        } else {
            document.getElementById('session_expired').style.display = 'block';
            document.getElementById('session_logout').style.display = 'none';
        }

    }
}
function get_url_param(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}



