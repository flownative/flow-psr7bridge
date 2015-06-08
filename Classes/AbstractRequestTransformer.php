<?php
namespace Flownative\Psr7Bridge;

use TYPO3\Flow\Http as FlowHttp;
use Psr\Http\Message as Psr;

/**
 * Abstract methods to
 */
abstract class AbstractRequestTransformer {

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

		$streamInstance = $this->createStreamFromResource($resource);
		return $streamInstance;
	}

	/**
	 * Takes a Flow HTTP request and transforms it into a \Psr\Http\Message\RequestInterface implementation instance.
	 *
	 * @param FlowHttp\Request $flowRequest The Flow Request to transform
	 * @return Psr\RequestInterface
	 */
	protected function createPsrFromFlowRequest(FlowHttp\Request $flowRequest) {
		$psrRequest = $this->createPsrRequestInstance();

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
	protected function createFlowFromPsrRequest(Psr\RequestInterface $psrRequest) {
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
	 * @param resource $resource
	 * @return Psr\StreamInterface
	 */
	protected function createStreamFromResource($resource) {
		return $this->streamConstructorClosure->__invoke($resource);
	}

	/**
	 * @return Psr\RequestInterface
	 */
	protected function createPsrRequestInstance() {
		return new $this->psrRequestImplementationClassName;
	}
}
