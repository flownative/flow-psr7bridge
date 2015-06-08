<?php
namespace Flownative\Psr7Bridge;

use TYPO3\Flow\Http as FlowHttp;
use Psr\Http\Message as Psr;

/**
 * Transformer to convert \TYPO3\Flow\Http\Request instances to \Psr\Http\Message\RequestInterface implementation instances and vice versa.
 * Additionally can extract a \Psr\Http\Message\StreamInterface instance from the Flow Request content.
 */
class RequestTransformer extends AbstractRequestTransformer implements RequestTransformerInterface {

	/**
	 * @param UriTransformerInterface $uriTransformer
	 * @param string $psrRequestImplementationClassName The PSR-7 RequestInterface implementation class to convert to.
	 * @param \Closure $streamContructorClosure A closure that should accept a PHP resource stream and return a PSR-7 StreamInterface instance wrapping the resource.
	 */
	public function __construct(UriTransformerInterface $uriTransformer, $psrRequestImplementationClassName, \Closure $streamContructorClosure) {
		$this->uriTransformer = $uriTransformer;
		$this->psrRequestImplementationClassName = $psrRequestImplementationClassName;
		$this->streamConstructorClosure = $streamContructorClosure;
	}

	/**
	 * Takes a Flow HTTP request and transforms it into a \Psr\Http\Message\RequestInterface implementation instance.
	 *
	 * @param FlowHttp\Request $flowRequest The Flow Request to transform
	 * @return Psr\RequestInterface
	 */
	public function transformFlowToPsrRequest(FlowHttp\Request $flowRequest) {
		return $this->createPsrFromFlowRequest($flowRequest);
	}

	/**
	 * Takes a \Psr\Http\Message\RequestInterface implementation instance and transforms it to a \TYPO3\Flow\Http\Request instance.
	 *
	 * @param Psr\RequestInterface $psrRequest The PSR-7 request to convert
	 * @return FlowHttp\Request
	 */
	public function transformPsrToFlowRequest(Psr\RequestInterface $psrRequest) {
		return $this->createFlowFromPsrRequest($psrRequest);
	}
}
