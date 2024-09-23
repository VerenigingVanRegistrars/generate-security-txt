<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\Formats\Keys\PKCS1;

// Check if phpseclib3 RSA classes are available
if (!class_exists(RSA::class) || !class_exists(PKCS1::class)) {
	@include_once dirname(__FILE__) . '/vendor/autoload.php';
}

// OpenPGP's library doesn't exist within composer, require if they aren't available yet
if (!class_exists('OpenPGP')) {
    require_once dirname(__FILE__) . '/openpgp-php/lib/openpgp.php';
}
if (!class_exists('OpenPGP_Crypt_RSA')) {
    require_once dirname(__FILE__) . '/openpgp-php/lib/openpgp_crypt_rsa.php';
}


class Securitytxt_Encryption
{
    public function __construct()
    {
        // Empty
    }


    /**
     * Generate keys and then sign the security.txt contents with these keys
     *
     * @param $name
     * @param $email
     * @param $file_contents
     * @param $passphrase
     * @return array
     * @throws Exception
     */
    function encrypt_securitytxt($name, $email, $file_contents, $passphrase = '')
    {
        $key_length = 2048;

        // Generate a key pair
        $privateKey = RSA::createKey($key_length);
        $privateKeyComponents = PKCS1::load($privateKey->toString('PKCS1'));

        $secretKeyPacket = new OpenPGP_SecretKeyPacket(array(
            'n' => $privateKeyComponents["modulus"]->toBytes(),
            'e' => $privateKeyComponents["publicExponent"]->toBytes(),
            'd' => $privateKeyComponents["privateExponent"]->toBytes(),
            'p' => $privateKeyComponents["primes"][1]->toBytes(),
            'q' => $privateKeyComponents["primes"][2]->toBytes(),
            'u' => $privateKeyComponents["coefficients"][2]->toBytes()
        ));

        // Assemble packets for the private key
        $packets = array($secretKeyPacket);

        $wkey = new OpenPGP_Crypt_RSA($secretKeyPacket);
        $fingerprint = $wkey->key()->fingerprint;
        $keyid = substr($fingerprint, -16);

        // Add a user ID packet
        $uid = new OpenPGP_UserIDPacket("$name <$email>");
        $packets[] = $uid;

        // Add a signature packet to certify the binding between the user ID and the key
        $sig = new OpenPGP_SignaturePacket(new OpenPGP_Message(array($secretKeyPacket, $uid)), 'RSA', 'SHA256');
        $sig->signature_type = 0x13;
        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_KeyFlagsPacket(array(0x01 | 0x02 | 0x04)); // Certify + sign + encrypt bits
        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_IssuerPacket($keyid);
        $m = $wkey->sign_key_userid(array($secretKeyPacket, $uid, $sig));

        // Append the signature to the private key packets
        $packets[] = $m->packets[2];

        // Assemble packets for the public key
        $publicPackets = array(new OpenPGP_PublicKeyPacket($secretKeyPacket));
        $publicPackets[] = $uid;
        $publicPackets[] = $sig;

        // Encrypt the private key with a passphrase
        $encryptedSecretKeyPacket = OpenPGP_Crypt_Symmetric::encryptSecretKey($passphrase, $secretKeyPacket);

        // Assemble the private key message
        $privateMessage = new OpenPGP_Message($packets);
        $privateMessage[0] = $encryptedSecretKeyPacket;

        // Enarmor the private key message
        $privateEnarmorKey = OpenPGP::enarmor($privateMessage->to_bytes(), "PGP PRIVATE KEY BLOCK");

        // Assemble the public key message
        $publicMessage = new OpenPGP_Message($publicPackets);

        // Enarmor the public key message
        $publicEnarmorKey = OpenPGP::enarmor($publicMessage->to_bytes(), "PGP PUBLIC KEY BLOCK");

        $SecurityTxtAdmin = new Generate_Security_Txt_Admin();
        $string = $SecurityTxtAdmin->get_securitytxt_contents(true);

        $m = $wkey->sign($string);

        /* Generate clearsigned data */
        $packets = $m->signatures()[0];

        $file_contents = "-----BEGIN PGP SIGNED MESSAGE-----\nHash: SHA256\n\n";
        // Output normalised data.  You could convert line endings here
        // without breaking the signature, but do not add any
        // trailing whitespace to lines.
	    $file_contents .= preg_replace("/^-/", "- -", $this->normalize_line_endings($packets[0]->data)) . "\n";
        $file_contents .= OpenPGP::enarmor($packets[1][0]->to_bytes(), "PGP SIGNATURE");


        $parsed_pubkey = OpenPGP_Message::parse($publicEnarmorKey);

        /* Parse signed message from file named "t" */
	    $file_contents = $this->normalize_line_endings($file_contents);
        $m = OpenPGP_Message::parse($file_contents);

        /* Create a verifier for the key */
        $verify = new OpenPGP_Crypt_RSA($parsed_pubkey);

        // We verify
        $verified = (bool)$verify->verify($m);

        return array(
            'signed_message' => $file_contents,
            'public_key' => $publicEnarmorKey,
            'private_key' => $privateEnarmorKey,
            'verified' => $verified
        );
    }


	/**
	 * Normalize line endings to guarentee security.txt standards
	 *
	 * @param $string
	 *
	 * @return array|string|string[]
	 */
	function normalize_line_endings($string) {
	    return str_replace(array("\r\n", "\r", "\n"), "\n", $string);
	}
}