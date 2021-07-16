window.LoginWithGoogleDataCallBack = function( response ) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', TempAccessOneTap.ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if(xhr.readyState === XMLHttpRequest.DONE) {
            var status = xhr.status;
            if ( status === 200 ) {
                var response = JSON.parse( xhr.responseText );

                if ( ! response.success ) {
                    alert( response.data );
                    return;
                }

                window.location = response.data.redirect;
            }
        }
    };
    console.log(response);
    xhr.send( 'action=validate_id_token&token=' + response.credential + '&state=' + TempAccessOneTap.state );
};
