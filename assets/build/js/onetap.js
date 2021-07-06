window.LoginWithGoogleDataCallBack = function( response ) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', TempAccessOneTap.ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    console.log(response);
    xhr.send( 'action=validate_id_token&token=' + response.credential );
};
