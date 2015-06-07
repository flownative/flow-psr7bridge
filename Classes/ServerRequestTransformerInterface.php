<?php
namespace Flownative\Psr7Bridge;

use Psr\Http\Message as Psr;
use TYPO3\Flow\Http as FlowHttp;

/**
 * Transformer to convert \TYPO3\Flow\Http\Request instances to \Psr\Http\Message\ServerRequestInterface implementation instances and vice versa.
 * Additionally can extract a \Psr\Http\Message\StreamInterface instance from the Flow Request content.
 */
interface ServerRequestTransformerInterface extends FlowRequestToStreamTransformerInterface {

	/**
	 * Takes a Flow HTTP request and transforms it into a \Psr\Http\Message\RequestInterface implementation instance.
	 *
	 * @param FlowHttp\Request $flowRequest The Flow Request to transform
	 * @return Psr\ServerRequestInterface
	 */
	public function transformFlowToPsrRequest(FlowHttp\Request $flowRequest);

	/**
	 * Takes a \Psr\Http\Message\RequestInterface implementation instance and transforms it to a \TYPO3\Flow\Http\Request instance.
	 *
	 * @param Psr\ServerRequestInterface $psrRequest The PSR-7 request to convert
	 * @return FlowHttp\Request
	 */
	public function transformPsrToFlowRequest(Psr\ServerRequestInterface $psrRequest);

}
