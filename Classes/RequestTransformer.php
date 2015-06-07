<?php
namespace Flownative\Psr7Bridge;

use TYPO3\Flow\Http as FlowHttp;
use Psr\Http\Message as Psr;

/**
 * Transformer to convert \TYPO3\Flow\Http\Request instances to \Psr\Http\Message\RequestInterface implementation instances and vice versa.
 * Additionally can extract a \Psr\Http\Message\StreamInterface instance from the Flow Request content.
 */
class RequestTransformer implements RequestTransformerInterface {

	/**
	 * The UriTransformer instance to use
	 *
	 * @var UriTransformerInterface
	 */
	protected $uriTransformer;

	/**
	 * The PSR-7 RequestInterface implementation class to convert to.
	 *
	 * @var string
	 */
	protected $psrRequestImplementationClassName;

	/**
	 * A function that accepts one argument which will be a PHP resource stream and returns a PSR-7 StreamInterface instance wrapping the given resource.
	 *
	 * @var \Closure
	 *
	 */
	protected $streamConstructorClosure;

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
		/** @var Psr\RequestInterface $psrRequest */
		$psrRequest = new $this->psrRequestImplementationClassName;

		$psrUri = $this->uriTransformer->transformFlowToPsrUri($flowRequest->getUri());
		$psrRequest = $psrRequest->withUri($psrUri);
		$psrRequest = $psrRequest->withMethod($flowRequest->getMethod());
		$psrRequest = $psrRequest->withProtocolVersion(substr($flowRequest->getVersion(), strrpos($flowRequest->getVersion(), '/') + 1));
		$psrRequest = $psrRequest->withBody($this->createPsrStreamFromFlowRequest($flowRequest));

		foreach ($flowRequest->getHeaders()->getAll() as $headerName => $headerValues) {
			$psrRequest = $psrRequest->withHeader($headerName, $headerValues);
		}

		return $psrRequest;
	}

	/**
	 * Takes a \Psr\Http\Message\RequestInterface implementation instance and transforms it to a \TYPO3\Flow\Http\Request instance.
	 *
	 * @param Psr\RequestInterface $psrRequest The PSR-7 request to convert
	 * @return FlowHttp\Request
	 */
	public function transformPsrToFlowRequest(Psr\RequestInterface $psrRequest) {
		$flowUri = $this->uriTransformer->transformPsrToFlowUri($psrRequest->getUri());
		$method = $psrRequest->getMethod();
		$body = $psrRequest->getBody();

		$flowRequest = FlowHttp\Request::create($flowUri, $method);
		$flowRequest->setContent($body->getContents());
		$flowRequest->setVersion('HTTP/' . $psrRequest->getProtocolVersion());
		foreach ($psrRequest->getHeaders() as $headerName => $values) {
			$flowRequest->setHeader($headerName, $values);
		}

		return $flowRequest;
	}

	/**
	 * Extracts a \Psr\Http\Message\StreamInterface instance from the body of a Flow request. As instanciation of the Stream can depend
	 * on the implementation you have to hand in a closure that can create the stream instance from a given PHP resource.
	 *
	 * @param FlowHttp\Request $request The Flow HTTP request to extract the content from
	 * @return Psr\StreamInterface
	 */
	public function createPsrStreamFromFlowRequest(FlowHttp\Request $request) {
		try {
			$resource = $request->getContent(TRUE);
		} catch (FlowHttp\Exception $exception) {
			$resource = fopen('php://temp', 'rw');
			fwrite($resource, $request->getContent());
		}

		$streamInstance = $this->streamConstructorClosure->__invoke($resource);
		return $streamInstance;
	}
}
