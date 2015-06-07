<?php
namespace Flownative\Psr7Bridge;

use TYPO3\Flow\Http as FlowHttp;
use Psr\Http\Message as Psr;

/**
 *
 */
class ResponseTransformer implements ResponseTransformerInterface {

	/**
	 * The PSR-7 ResponseInterface implementation to use.
	 *
	 * @var string
	 */
	protected $psrResponseImplementationClassName;

	/**
	 * A closure that accepts a string and returns a PSR-7 StreamInterface instance wrapping this string as content.
	 *
	 * @var \Closure
	 */
	protected $streamConstructorClosure;

	/**
	 * @param string $psrResponseImplementationClassName The PSR-7 ResponseInterface implementation class name to use
	 * @param \Closure $streamConstructorClosure A closure that accepts a string argument and returns a \Psr\Http\Message\StreamInterface implemenatation instance which represents the given string content.
	 */
	public function __construct($psrResponseImplementationClassName, \Closure $streamConstructorClosure) {
		$this->psrResponseImplementationClassName = $psrResponseImplementationClassName;
		$this->streamConstructorClosure = $streamConstructorClosure;
	}

	/**
	 * Converts a given Flow HTTP response object to a \Psr\Http\Message\ResponseInterface implementation object.
	 *
	 * @param FlowHttp\Response $flowResponse The Flow HTTP response to transform
	 * @return Psr\ResponseInterface
	 */
	public function transformFlowToPsrResponse(FlowHttp\Response $flowResponse) {
		/** @var Psr\ResponseInterface $psrResponse */
		$psrResponse = new $this->psrResponseImplementationClassName;

		$psrResponse = $psrResponse->withBody($this->streamConstructorClosure->__invoke($flowResponse->getContent()));
		$psrResponse = $psrResponse->withStatus($flowResponse->getStatusCode(), $flowResponse->getStatusLine());
		$psrResponse = $psrResponse->withProtocolVersion(substr($flowResponse->getVersion(), strrpos($flowResponse->getVersion(), '/') + 1));

		foreach ($flowResponse->getHeaders()->getAll() as $headerName => $values) {
			$psrResponse = $psrResponse->withHeader($headerName, $values);
		}

		return $psrResponse;
	}

	/**
	 * Convertes a given PSR-7 \Psr\Http\Message\ResponseInterface implementation object to a \TYPO3\Flow\Http\Response object.
	 *
	 * @param Psr\ResponseInterface $psrResponse The PSR-7 ResponseInterface implementation object to transform
	 * @return FlowHttp\Response
	 */
	public function transformPsrToFlowResponse(Psr\ResponseInterface $psrResponse) {
		$flowResponse = new FlowHttp\Response();

		$flowResponse->setContent($psrResponse->getBody()->__toString());
		$flowResponse->setVersion('HTTP/' . $psrResponse->getProtocolVersion());
		$flowResponse->setStatus($psrResponse->getStatusCode(), $psrResponse->getReasonPhrase());
		foreach ($psrResponse->getHeaders() as $headerName => $values) {
			// TODO: Circumvents https://jira.typo3.org/browse/FLOW-305, can be removed if fixed.
			if ($headerName === 'Content-Type') {
				if (is_array($values) && isset($values[0])) {
					$values = $values[0];
				}
			}
			$flowResponse->setHeader($headerName, $values);
		}

		return $flowResponse;
	}
}
