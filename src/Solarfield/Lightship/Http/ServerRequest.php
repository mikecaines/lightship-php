<?php
declare(strict_types=1);

namespace Solarfield\Lightship\Http;

use GuzzleHttp\Psr7\Uri;

class ServerRequest extends \GuzzleHttp\Psr7\ServerRequest {
	public static function fromGlobals() {
		$request = parent::fromGlobals();
		
		// let any resulting rewrite uri take precedence (e.g. apache mod_rewrite)
		if (array_key_exists('REDIRECT_URL', $request->getServerParams())) {
			$uri = $request->getServerParams()['REDIRECT_URL'];
			
			if (array_key_exists('REDIRECT_QUERY_STRING', $request->getServerParams())) {
				$query = $request->getServerParams()['REDIRECT_QUERY_STRING'];
				
				if ($query !== '') {
					$uri .= '?' . $_SERVER['REDIRECT_QUERY_STRING'];
				}
			}
			
			$request = $request->withUri(new Uri($uri));
		}
		
		return $request;
	}
}
