<?php

namespace Chip_Store;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Encryptor
 *
 * Handles the encryption and decryption of data.
 */
class Encryptor {

    /**
     * The encryption key.
     * We use the SECURE_AUTH_KEY constant from the environment config as the key.
     *
     * @var string
     */
    private $key;

    /**
     * The initialization vector.
     *
     * @var string
     */
    private $iv;

    /**
     * The encryption algorithm.
     * We use the AES-256-CBC algorithm.
     */
    private const ALGORITHM = 'aes-256-cbc';

    /**
     * The single instance of the class.
     *
     * @var Encryptor
     */
    private static $instance = null;

    /**
     * Encryptor constructor.
     */
    private function __construct() {
        $this->key = SECURE_AUTH_KEY;
        $this->iv = substr( SECURE_AUTH_SALT, 0, 16 ); // Use the first 16 characters of the salt as the IV.
    }

    /**
     * Gets the single instance of the class.
     *
     * @return Encryptor The single instance of the class.
     */
    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Encrypts the given data.
     *
     * @param string $data The data to encrypt.
     *
     * @return string The encrypted data.
     */
    public function encrypt( $data ) {
        return openssl_encrypt( $data, self::ALGORITHM, $this->key, 0, $this->iv );
    }

    /**
     * Decrypts the given data.
     *
     * @param string $data The data to decrypt.
     *
     * @return string The decrypted data.
     */
    public function decrypt( $data ) {
        return openssl_decrypt( $data, self::ALGORITHM, $this->key, 0, $this->iv );
    }
}