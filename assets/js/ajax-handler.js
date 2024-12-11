/**
 * Handles the AJAX requests to the server.
 */
class AjaxHandler {

    /**
     * Sends an AJAX request to the server.
     * 
     * @param ajaxUrl The URL to send the AJAX request to.
     * @param method The HTTP method to be used for the request.
     * @param body The data to be sent to the server.
     */
    static sendRequest( ajaxUrl, method, body ) {
        fetch( ajaxUrl, { method, body } )
        .then( response => response.json() )
        .then( this.successCallback )
        .catch( this.errorCallback );
    }

    /**
     * The callback function to be executed when the request is successful.
     *
     * @param response The response from the server.
     */
    successCallback( response ) {}

    /**
     * The callback function to be executed when the request fails. 
     *
     * @param error The error from the server.
     */
    errorCallback( error ) {
        console.error( 'Error:', error );
    }

        /**
     * Constructs the data payload to be sent to the server.
     * Appends action, nonce, other data to the FormData object.
     *
     * @param {string} action The action to be performed on the server.
     * @param {string} nonce The nonce value to verify the request.
     * @param {string} key The key to be used for the data payload.
     * @param {object} data The data to be sent to the server.
     *
     * @returns {FormData} The constructed data payload.
     */
    static constructPayload( action, nonce, key, data ) {
        const data = new FormData();
        // Append action and nonce to the data payload
        data.append( 'action', action );
        data.append( 'nonce', nonce );
        
        // Append data payload
        data.append( key, data );

        return data;
    }
}