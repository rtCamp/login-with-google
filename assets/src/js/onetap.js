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

                try {

                    var redirect_to = new URL( response.data.redirect );
                    var homeurl = new URL( TempAccessOneTap.homeurl );

                    if ( redirect_to.host !== homeurl.host ) {
                        throw new URIError( wp.i18n.__( 'Invalid URL for Redirection', 'login-with-google' ) );
                    }

                } catch ( e ) {
                    alert( e.message );
                    return;
                }

                window.location = response.data.redirect;
            }
        }
    };
    xhr.send( 'action=validate_id_token&token=' + response.credential + '&state=' + TempAccessOneTap.state );
};
