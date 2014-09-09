<?php 
namespace Dsc;

class Cookie extends \Dsc\Singleton
{
	/**
	 * How long is forever (in minutes)?
	 *
	 * @var int
	 */
	const forever = 525600;

	/**
	 * The cookies that have been set.
	 *
	 * @var array
	 */
	public static $jar = array();
	
	/**
	 * 
	 */
	public function __construct()
	{
	    static::$jar = $_COOKIE;
	}

	/**
	 * Determine if a cookie exists.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public static function has($name)
	{
		return !is_null(static::get($name));
	}

	/**
	 * Get the value of a cookie.
	 *
	 * @param  string  $name
	 * @param  mixed   $default
	 * @return string
	 */
	public static function get($name, $default = null)
	{
		if (isset(static::$jar[$name])) 
		{
		    return static::$jar[$name]['value'];
		}

		return \Dsc\ArrayHelper::get( $_COOKIE, $name, $default);
	}

	/**
	 * Set the value of a cookie.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  int     $expiration
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @return void
	 */
	public static function set($name, $value, $expiration = 0, $path = '/', $domain = null, $secure = false)
	{
		if ($expiration !== 0)
		{
			$expiration = time() + ($expiration * 60);
		}

		// If the secure option is set to true, yet the request is not over HTTPS
		if ($secure && !\Dsc\Url::isSecure())
		{
			throw new \Exception("Attempting to set secure cookie over HTTP.");
		}

		static::$jar[$name] = compact('name', 'value', 'expiration', 'path', 'domain', 'secure');
		
		setcookie( $name, $value, $expiration, $path, $domain, $secure );
	}

	/**
	 * Set a "permanent" cookie. The cookie will last for one year.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @return bool
	 */
	public static function forever($name, $value, $path = '/', $domain = null, $secure = false)
	{
		return static::set($name, $value, static::forever, $path, $domain, $secure);
	}

	/**
	 * Delete a cookie.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @return bool
	 */
	public static function forget($name, $path = '/', $domain = null, $secure = false)
	{
		return static::set($name, null, -2000, $path, $domain, $secure);
	}

}