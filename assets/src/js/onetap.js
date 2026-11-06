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

    var stateToSend = TempAccessOneTap.state;
    try {
        var isHttps = window.location.protocol === 'https:';
        var cookieName = ( TempAccessOneTap && TempAccessOneTap.cookie_name ) ? TempAccessOneTap.cookie_name : ( isHttps ? '__Host-lwg_state_token' : 'lwg_state_token' );
        var randomBytes = new Uint8Array( 16 );
        window.crypto.getRandomValues( randomBytes );
        var freshToken = Array.from( randomBytes, function( byte ) {
            return ( '0' + ( byte & 0xFF ).toString( 16 ) ).slice( -2 );
        } ).join( '' );
        var secureFlag = ( isHttps || cookieName.indexOf( '__Host-' ) === 0 ) ? '; Secure' : '';
        document.cookie = cookieName + '=' + freshToken + '; Path=/; max-age=3600; SameSite=Lax' + secureFlag;

        var decoded = JSON.parse( atob( TempAccessOneTap.state ) );
        if ( decoded && typeof decoded === 'object' ) {
            decoded.state_token = freshToken;
            stateToSend = btoa( JSON.stringify( decoded ) );
        }
    } catch ( e ) {}

    xhr.send( 'action=validate_id_token&token=' + encodeURIComponent( response.credential ) + '&state=' + encodeURIComponent( stateToSend ) );
};
