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
        const couponContainer = this.couponField.closest( '.e-coupon-box' );
        if ( ! couponContainer ) {
            return;
        }
        this.insertChipBox( couponContainer );
    }

    insertChipBox( couponContainer ) {
        const chipContainer = couponContainer.cloneNode( true );
        chipContainer.innerHTML = chipContainer.innerHTML.replace( /coupon/g, 'chip' );
        couponContainer.parentNode.insertBefore( chipContainer, couponContainer );

        // Always shown to logged in users
        if ( chipStoreCheckout.isLoggedIn ) {
            this.changeField( chipContainer, 'code' );
            return;
        }

        // Shown to guests if no chip id is found in the current session
        if ( ! chipStoreCheckout.guestChip.id ) {
            this.changeField( chipContainer, 'code' );
            return;
        }

        this.changeField( chipContainer, 'amount' );
    }

    changeField( chipContainer, context ) {
        const chipField = document.getElementById( 'chip_code' );
        const submitButton = chipContainer.querySelector( 'button[type="submit"]' );

        this.changeText( chipContainer, chipField, context );

        // Number input for amount context and restrict input to max subtotal
        if ( 'amount' === context ) {
            chipField.type = 'number';
            chipField.addEventListener( 'input', () => {
                if ( parseFloat( chipField.value ) > chipStoreCheckout.woocommerce.subtotal ) {
                    chipField.value = chipStoreCheckout.woocommerce.subtotal;
                }
            } );
        }

        // "Apply" button to send the chip code/amount
        submitButton.addEventListener( 'click', () => this.sendAjax(
            chipStoreCheckout.ajax.action[ context ],
            chipStoreCheckout.ajax.keys[ context ],
            chipField.value
        ) );
    }

    changeText( chipContainer, chipField, context ) {
        const paragraph = chipContainer.querySelector( 'p.e-woocommerce-chip-nudge' );
        const label = chipContainer.querySelector( 'label' );

        label.textContent = chipStoreCheckout.text[ context ].label;
        chipField.placeholder = chipStoreCheckout.text[ context ].placeholder;

        // Replace nudge paragraph text and re-add the anchor
        if ( paragraph ) {
            const anchor = paragraph.querySelector( 'a' );
            paragraph.textContent = chipStoreCheckout.text[ context ].nudge;
            if ( anchor ) {
                paragraph.appendChild( anchor );
                anchor.addEventListener( 'click', (event) => {
                    event.preventDefault();
                    const chipAnchorDiv = chipContainer.querySelector( 'div.e-chip-anchor' );
                    if ( chipAnchorDiv ) {
                        chipAnchorDiv.style.display = 'none' === chipAnchorDiv.style.display ? '' : 'none';
                    }
                } );
                // Change expand anchor text
                anchor.textContent = chipStoreCheckout.text[ context ].expand;
            }
        }
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