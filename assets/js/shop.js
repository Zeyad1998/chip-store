/**
 * Class representing a Shop.
 * Handles the logic for sending a chip code.
 */
class Shop {

    /**
     * Creates an instance of Shop.
     * Initializes the chip code and user login status.
     */
    constructor() {
        this.sendChipCode();
    }

    /**
     * Sends the chip code to the server.
     * Constructs the data payload with the chip code and user information,
     * and sends it via a POST request to the server.
     */
    sendChipCode() {
        this.chipCode = this.getAndCleanChipCode();
        if ( ! this.chipCode ) {
            return;
        }

        this.sendAjax( this.constructPayload() );
    }

    /**
     * Constructs the data payload to be sent to the server.
     * Appends action, nonce, chip code, and user information to the FormData object.
     * @returns {FormData} The constructed data payload.
     */
    constructPayload() {
        const data = new FormData();
        // Append action and nonce to the data payload
        data.append( 'action', chipStoreShop.ajax.action );
        data.append( '_ajax_nonce', chipStoreShop.ajax.nonce );
        
        // Append chip code to the data payload
        data.append( chipStoreShop.chipCodeKey, this.chipCode );

        return data;
    }

    /**
     * Sends the data payload via a POST request to the server.
     */
    sendAjax( body ) {
        fetch( chipStoreShop.ajax.url, {
            method: 'POST',
            body
        } )
        .then( response => response.json() )
        .then( this.successCallback )
        .catch( this.errorCallback );
    }

    /**
     * Callback function to handle a successful response from the server.
     *
     * @param {*} response The response from the server.
     */
    successCallback( response ) {
        if ( true !== response.success ) {
            console.log( response );
            if ( response.data ) {
                console.log( response.data );
                alert( response.data );
            } else {
                console.log( response );
                alert( 'An unknown error occurred.' );
            }
        }
    }

    /**
     * Callback function to handle an error response from the server.
     *
     * @param {*} error The error from the server.
     */
    errorCallback( error ) {
        console.error( 'Error:', error );
        alert( 'A server side error has occurred while sending the chip code.' );
    }
}

document.addEventListener( 'DOMContentLoaded', () => {
    new Shop();
} );
