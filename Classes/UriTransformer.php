<?php
namespace Flownative\Psr7Bridge;

use TYPO3\Flow\Http as Flow;
use \Psr\Http\Message as Psr;

/**
 * Transformer to convert \TYPO3\Flow\Http\Uri instances to \Psr\Http\Message\UriInterface implementation instances and vice versa.
 */
class UriTransformer {

	/**
	 * Takes a Flow Uri instance and converts it to an instance of the \Psr\Http\Message\UriInterface implementation class given.
	 *
	 * @param Flow\Uri $flowUri
	 * @param string $psrImplementationClassName
	 * @return Psr\UriInterface
	 */
	public function transformFlowToPsrUri(Flow\Uri $flowUri, $psrImplementationClassName) {
		/** @var $psrUri Psr\UriInterface */
		$psrUri = new $psrImplementationClassName;

		$psrUri = $psrUri->withScheme($flowUri->getScheme());

		// According to \Psr\Http\Message\UriInterface::withUserInfo() the username must be a string, so lets make sure we have something to set here before.
		$flowUriUsername = $flowUri->getUsername();
		if ($flowUriUsername !== NULL) {
			$psrUri = $psrUri->withUserInfo($flowUri->getUsername(), $flowUri->getPassword());
		}

		$psrUri = $psrUri->withHost($flowUri->getHost());
		$psrUri = $psrUri->withPort($flowUri->getPort());
		$psrUri = $psrUri->withPath($flowUri->getPath());

		$flowUriQueryString = $flowUri->getQuery();
		if ($flowUriQueryString !== NULL) {
			$psrUri = $psrUri->withQuery($flowUri->getQuery());
		}

		$psrUri = $psrUri->withFragment($flowUri->getFragment());

		return $psrUri;
	}

	/**
	 * Takes a \Psr\Http\Message\UriInterface implementation instance and converts it to a Flow Uri.
	 *
	 * @param Psr\UriInterface $psrUri
	 * @return Flow\Uri
	 */
	public function transformPsrToFlowUri(Psr\UriInterface $psrUri) {
		$flowUri = new Flow\Uri($psrUri->__toString());

		return $flowUri;
	}
}
