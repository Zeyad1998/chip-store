/**
 * Class to handle chip code from URL query string.
 */
class MyAccount { 
    /**
     * Constructor initializes the chip code and checks the URL for chip code.
     */
    constructor() { 
        this.chipCode = null; 
        this.checkAndStoreChipCode(); 
    }

    /**
     * Checks if the user is logged in and redirects to the shop page if true.
     */
    redirectToShopIfLoggedIn() {
        if ( window.chipStoreMyAccount.isLoggedIn ) {
            window.location.href = '/shop';
        }
    }

    /**
     * Checks the URL for the 'chip_code' query parameter, stores it, and removes it from the URL.
     */
    checkAndStoreChipCode() { 
        const urlParams = new URLSearchParams( window.location.search ); 
        if ( ! urlParams.has( 'chip_code' ) || sessionStorage.getItem( 'chip_code' ) ) { // If chip code is not in the URL or already stored in session storage
            return;
        }

        this.chipCode = urlParams.get( 'chip_code' ); 
        urlParams.delete( 'chip_code' ); 
        this.updateUrl( urlParams );
        sessionStorage.setItem( 'chip_code', this.chipCode );
        this.redirectToShopIfLoggedIn();
    }

    /**
     * Updates the URL by removing the 'chip_code' query parameter without redirecting.
     * 
     * @param { URLSearchParams } urlParams - The URLSearchParams object without the 'chip_code' parameter.
     */
    updateUrl( urlParams ) { 
        const newUrl = window.location.pathname + ( urlParams.toString() ? '?' + urlParams.toString() : '' );
        window.history.replaceState( {}, document.title, newUrl ); 
    }
} 

const myAccount = new MyAccount();
