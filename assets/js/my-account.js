/**
 * Class to handle chip code from URL query string.
 */
class MyAccount { 
    /**
     * Constructor initializes the chip code and checks the URL for chip code.
     */
    constructor() { 
        this.processChipCode(); 
    }

    /**
     * Processes the chip code from the URL query string.
     */
    processChipCode() { 
        const urlParams = new URLSearchParams( window.location.search ); 
        this.chipCode = urlParams.get( chipStore.chipCodeKey );

        if ( ! this.chipCode ) {
            return;
        }

        if ( chipStore.isLoggedIn ) {
            this.sendAjaxAndRedirectToShop();
        } else {
            this.handleGuestButton();
        }
    }

    /**
     * Finds the button with the ID "continue-as-a-guest-btn" and adds an event listener to send the chip code via AJAX and then redirect to the shop page.     */
    handleGuestButton() {
        const guestButton = document.getElementById( 'continue-as-guest-btn' );
        if ( ! guestButton ) {
            return;
        }

        guestButton.addEventListener( 'click', ( event ) => {
            event.preventDefault();
            this.sendAjaxAndRedirectToShop();
        } );
    }

    sendAjaxAndRedirectToShop() {
        this.sendAjax()
            .then( () => window.location.href = '/shop' );
    }

    /**
     * Sends the data payload via a POST request to the server.
     *
     * @returns {Promise} The fetch promise.
     */
    async sendAjax() {
        const data = new FormData();
        // Append action and nonce to the data payload
        data.append( 'action', chipStore.ajax.action );
        data.append( '_ajax_nonce', chipStore.ajax.nonce );
        
        // Append chip code to the data payload
        data.append( chipStore.chipCodeKey, this.chipCode );

        return fetch( chipStore.ajax.url, {
            method: 'POST',
            body: data
        } )
            .catch( this.errorCallback )
            .then( ( response ) => response.json() )
            .then( this.successCallback )
    }

    /**
     * Callback function to handle a successful response from the server.
     *
     * @param {*} response The response from the server.
     */
    successCallback( response ) {
        if ( true !== response.success ) {
            if ( response.data ) {
                alert( response.data );
            } else {
                alert( 'An unknown error occurred.' );
            }
            return Promise.reject( response );
        }
    }

    /**
     * Callback function to handle an error response from the server.
     *
     * @param {*} error The error from the server.
     */
    errorCallback( error ) {
        alert( 'A server side error has occurred while sending the chip code.' );
    }
} 

const myAccount = new MyAccount();
