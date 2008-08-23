<?php

class frameless
{
	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		static $regex_body, $replace_body;

		if ( ! $regex_body || ! $replace_body )
		{
			// Define our preg arrays
			$regex_body		= array();
			$replace_body	= array();

			//convert relative links into absolute links
			//$regex_body[]	= '#(href|src)="./(.*?)"#';
			//$replace_body[]	= 'href="'.$fullURL.'#$1"';

			// Internal links
			$regex_body[]	= '#href="\#(.*?)"#';
			$replace_body[]	= 'href="'.$fullURL.'#$1"';

			// Site URLs
			$regex_body[]	= "#$integratedURL(.*)\?(.*)\"#mS";
			$replace_body[]	= $baseURL . 'jfile=$1&$2"';

		}

		$buffer = preg_replace($regex_body, $replace_body, $buffer);
	}

	function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		static $regex_header, $replace_header;

		if ( ! $regex_header || ! $replace_header )
		{
			// Define our preg arrays
			$regex_header		= array();
			$replace_header	= array();

			//convert relative links into absolute links
			$regex_header[]	= '#(href|src)="./(.*?)"#mS';
			$replace_header[]	= 'href="'.$integratedURL.'#$1"';

		}

		$buffer = preg_replace($regex_header, $replace_header, $buffer);	}


}



