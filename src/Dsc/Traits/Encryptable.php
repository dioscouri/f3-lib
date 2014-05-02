<?php 

namespace Dsc\Traits;

/**
 * 
 * This trait requires phpseclib/phpseclib repository to be present in your composer.json file
 */
trait Encryptable{
	
	/*
	 * This trait requires this trait:
	 * \Dsc\Traits\ErrorTracking;
	 */
	
	
	/**
	 * This method returns encryption key for this cypher
	 * By default, the encryption key is stored in a file public/../config/key.ini
	 * This file should be owned by apache user and only apache user should be able to read it
	 * 
	 * NOTE: DES, 3DES, Blowfish are considered depreciated
	 * 
	 * Size of key based on used cipher (just change key, no additional operations required):
	 * - DES => 64bits
	 * - 3DES => 192bits
	 * - AES => 128bits, 192bits or 256bits
	 * - Rijndael => 128bits, 160bits, 192bits, 224bits, 256bits
	 * - Twofish => 128bits, 192bits, 256bits
	 * - Blowfish => in length from 32 bits to 448 in steps of 8
	 * - RC4 => 192bits
	 * - RC2 => in length from 8 bits to 1024 in steps of 8
	 * 
	 * @return	Encryption key
	 */
	private function getEncryptionKey(){
		$f3 = \Base::instance();
		$f3->config( $f3->get('PATH_ROOT') . 'config/common.config.ini');
		$key = $f3->get('encryption.key');
		if( empty( $key )  ){
			$this->setError( "No encryption key was founded!" );
			$key = '';
		}
		
		return $key;
	}

	/**
	 * This method returns instance of cipher. In case you need to use other than the default cipher,
	 * you can override it from model 
	 * 
	 * @return Initialized instance of cipher
	 */
	private function getCipher(){
		static $cipher = null;
		if( $cipher == null ) {
			$cipher = new \Crypt_Rijndael();
			$key = $this->getEncryptionKey();
			if( strlen( $key ) ) {
				$cipher->setKey( $key );
			} else {
				$cipher = null;
				return null;
			}
			$cipher->setBlockLength(224);
		}
		
		return $cipher;
	}
	
	/**
	 * This method uses actually selected cipher and returns encrypted content of the field
	 * 
	 * @param	$text		text to be encrypted
	 * 
	 * @return	Content of the text
	 */
	public function encryptText($text){
		return $this->getCipher()->encrypt( $text );
	}

	/**
	 * This method uses actually selected cipher and returns encrypted content of the field
	 *
	 * @param	$text		text to be encrypted
	 *
	 * @return	Content of the text in base64 format
	 */
	public function encryptTextBase64($text){
		return base64_encode( $this->getCipher()->encrypt( $text ) );
	}
	
	/**
	 * This method uses actually selected cipher and returns encrypted content of the field
	 *
	 * @param	$text		text to be encrypted
	 *
	 * @return	Content of the text in base64 format and escaped for mongo query
	 */
	public function encryptTextMongo($text){
        return preg_replace( '/\+/', '\\+' , base64_encode( $this->getCipher()->encrypt( $text ) ) );
	}
	
	/**
	 * This method uses actually selected cipher and returns decrypted content of the field
	 * 
	 * @param	$text		text to be decrypted
	 * 
	 * @return	Content of the text
	 */
	public function decryptText($text){
	    $text = trim($text);
	    if (empty($text)) {
	        return null;
	    }	    
		return $this->getCipher()->decrypt( $text );
	}
	
	/**
	 * This method uses actually selected cipher and returns decrypted content of the field
	 * 
	 * @param	$text		text to be decrypted in base 64 format
	 * 
	 * @return	Content of the text
	 */
	public function decryptTextBase64($text){
	    $text = trim($text);
	    if (empty($text)) {
	    	return null;
	    }
		return $this->getCipher()->decrypt( base64_decode( $text ) );
	}
}