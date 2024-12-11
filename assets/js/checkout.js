/**
 * Checkout class to handle the checkout process.
 */
class Checkout {
    constructor() {
        this.observeDOM();
    }

    observeDOM() {
        const observer = new MutationObserver( ( mutations ) => {
            mutations.forEach( ( mutation) => {
                if ( mutation.addedNodes.length ) {
                    if ( ! this.couponField ) {
                        this.initializeElements();
                    } else {
                        observer.disconnect();
                        return;
                    }
                    
                }
            } );
        } );

        observer.observe( document.body, {
            childList: true,
            subtree: true
        } );
    }

    initializeElements() {
        this.couponField = document.getElementById( 'coupon_code' );
        if ( ! this.couponField ) {
            return;
        }

        this.couponContainer = this.couponField.closest( '.e-coupon-box' );
        this.paragraph = this.couponContainer.querySelector( 'p.e-woocommerce-coupon-nudge' );
        this.expandAnchor = this.couponContainer.querySelector( 'a.e-show-coupon-form' );
        this.couponLabel = this.couponContainer.querySelector( 'label' );
        this.submitButton = this.couponContainer.querySelector( 'button[type="submit"]' );

        this.modifyCouponField();
    }

    modifyCouponField() {
        if ( ! this.couponField || ! this.submitButton || ! this.couponContainer ) {
            return;
        }

        // Always shown to logged in users
        if ( chipStoreCheckout.isLoggedIn ) {
            this.changeField( 'code' );
            return;
        }

        // Shown to guests if no chip id is found in the current session
        if ( ! chipStoreCheckout.guestChip.id ) {
            this.changeField( 'code' );
            return;
        }

        this.changeField( 'amount' );
    }

    changeField( context ) {
        this.couponLabel.textContent = chipStoreCheckout.text[ context ].label;
        this.couponField.placeholder = chipStoreCheckout.text[ context ].field;

        if ( 'amount' === context ) {
            this.couponField.type = 'number';
            this.couponField.addEventListener( 'input', () => {
                if ( parseFloat( this.couponField.value ) > chipStoreCheckout.woocommerce.subtotal ) {
                    this.couponField.value = chipStoreCheckout.woocommerce.subtotal;
                }
            } );
        }

        this.submitButton = this.removeAllEventListeners( this.submitButton );
        this.submitButton.addEventListener( 'click', () => this.sendAjax(
            chipStoreCheckout.ajax.action[ context ],
            chipStoreCheckout.ajax.keys[ context ],
            this.couponField.value
        ) );
    }

    /**
     * Removes all event listeners from the given element.
     * 
     * @param {HTMLElement} button - The element from which to remove the event listeners.
     */
    removeAllEventListeners( button ) {
        const newButton = button.cloneNode( true );
        button.parentNode.replaceChild( newButton, button );
        return newButton;
    }

    /**
     * Sends the data payload via a POST request to the server.
     * 
     */
    sendAjax( action, key, value ) {
        const payloadData = {};
        payloadData[ key ] = value;
        if ( chipStoreCheckout.guestChip.id ) {
            payloadData[ chipStoreCheckout.ajax.keys.guestChipId ] = chipStoreCheckout.guestChip.id;
        }

        const body = this.constructPayload(
            action,
            payloadData
        );

        fetch( chipStoreCheckout.ajax.url, {
            method: 'POST',
            body
        } )
        .then( response => response.json() )
        .then( this.successCallback )
        .catch( this.errorCallback );
    }

    /**
     * Constructs the data payload to be sent to the server.
     * Appends action, nonce, other data to the FormData object.
     *
     * @param {string} action - The action to be performed.
     * @param {Object} additionalData - The data to be sent to the server.
     *
     * @returns {FormData} The constructed data payload.
     */
    constructPayload( action, additionalData ) {   
        const data = new FormData();
        // Append action and nonce to the data payload
        data.append( 'action', action );
        data.append( '_ajax_nonce', chipStoreCheckout.ajax.nonce );
        
        // Append extra data
        Object.entries( additionalData ).forEach( ( [ key, value ] ) => {
            data.append( key, value );
        } );

        return data;
    }

    /**
     * Callback function to handle a successful response from the server.
     *
     * @param {*} response The response from the server.
     */
    successCallback( response ) {
        if ( true !== response.success ) {
            alert( response.data );
        } else {
            location.reload();
        }
    }

    /**
     * Callback function to handle an error response from the server.
     *
     * @param {*} error The error from the server.
     */
    errorCallback( error ) {
        console.error( 'Error:', error );
    }
}

document.addEventListener( 'DOMContentLoaded', () => {
    new Checkout();
} );