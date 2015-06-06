<?php
namespace Flownative\Psr7Bridge;

use TYPO3\Flow\Http as FlowHttp;
use Psr\Http\Message as Psr;

/**
 *
 */
class ResponseTransformer {

	/**
	 * Converts a given Flow HTTP response object to a \Psr\Http\Message\ResponseInterface implementation object.
	 *
	 * @param FlowHttp\Response $flowResponse The Flow HTTP response to transform
	 * @param string $psrResponseImplementationClassName The PSR-7 ResponseInterface implementation class name to use
	 * @param \Closure $psrStreamClosure A closure that accepts a string argument and returns a \Psr\Http\Message\StreamInterface implemenatation instance which represents the given string content.
	 * @return Psr\ResponseInterface
	 */
	public function transformFlowToPsrResponse(FlowHttp\Response $flowResponse, $psrResponseImplementationClassName, \Closure $psrStreamClosure) {
		/** @var Psr\ResponseInterface $psrResponse */
		$psrResponse = new $psrResponseImplementationClassName;

		$psrResponse = $psrResponse->withBody($psrStreamClosure($flowResponse->getContent()));
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
			$flowResponse->setHeader($headerName, $values);
		}

		return $flowResponse;
	}
}
