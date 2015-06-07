<?php
namespace Flownative\Psr7Bridge;

use Psr\Http\Message as Psr;
use TYPO3\Flow\Http as Flow;

/**
 * Transformer to convert \TYPO3\Flow\Http\Uri instances to \Psr\Http\Message\UriInterface implementation instances and vice versa.
 */
interface UriTransformerInterface {
	/**
	 * Takes a Flow Uri instance and converts it to an instance of the \Psr\Http\Message\UriInterface implementation class.
	 *
	 * @param Flow\Uri $flowUri
	 * @return Psr\UriInterface
	 */
	public function transformFlowToPsrUri(Flow\Uri $flowUri);

	/**
	 * Takes a \Psr\Http\Message\UriInterface implementation instance and converts it to a Flow Uri.
	 *
	 * @param Psr\UriInterface $psrUri
	 * @return Flow\Uri
	 */
	public function transformPsrToFlowUri(Psr\UriInterface $psrUri);
}
