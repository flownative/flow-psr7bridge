<?php
namespace Flownative\Psr7Bridge;

use Psr\Http\Message as Psr;
use TYPO3\Flow\Http as FlowHttp;

/**
 * Transformer to convert \TYPO3\Flow\Http\Request instances to \Psr\Http\Message\RequestInterface implementation instances and vice versa.
 * Additionally can extract a \Psr\Http\Message\StreamInterface instance from the Flow Request content.
 */
interface RequestTransformerInterface {
	/**
	 * Takes a Flow HTTP request and transforms it into a \Psr\Http\Message\RequestInterface implementation instance.
	 *
	 * @param FlowHttp\Request $flowRequest The Flow Request to transform
	 * @return Psr\RequestInterface
	 */
	public function transformFlowToPsrRequest(FlowHttp\Request $flowRequest);

	/**
	 * Takes a \Psr\Http\Message\RequestInterface implementation instance and transforms it to a \TYPO3\Flow\Http\Request instance.
	 *
	 * @param Psr\RequestInterface $psrRequest The PSR-7 request to convert
	 * @return FlowHttp\Request
	 */
	public function transformPsrToFlowRequest(Psr\RequestInterface $psrRequest);

	/**
	 * Extracts a \Psr\Http\Message\StreamInterface instance from the body of a Flow request. As instanciation of the Stream can depend
	 * on the implementation you have to hand in a closure that can create the stream instance from a given PHP resource.
	 *
	 * @param FlowHttp\Request $request The Flow HTTP request to extract the content from
	 * @return Psr\StreamInterface
	 */
	public function createPsrStreamFromFlowRequest(FlowHttp\Request $request);
}
