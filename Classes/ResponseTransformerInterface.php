<?php
namespace Flownative\Psr7Bridge;

use Psr\Http\Message as Psr;
use TYPO3\Flow\Http as FlowHttp;

/**
 *
 */
interface ResponseTransformerInterface {
	/**
	 * Converts a given Flow HTTP response object to a \Psr\Http\Message\ResponseInterface implementation object.
	 *
	 * @param FlowHttp\Response $flowResponse The Flow HTTP response to transform
	 * @return Psr\ResponseInterface
	 */
	public function transformFlowToPsrResponse(FlowHttp\Response $flowResponse);

	/**
	 * Convertes a given PSR-7 \Psr\Http\Message\ResponseInterface implementation object to a \TYPO3\Flow\Http\Response object.
	 *
	 * @param Psr\ResponseInterface $psrResponse The PSR-7 ResponseInterface implementation object to transform
	 * @return FlowHttp\Response
	 */
	public function transformPsrToFlowResponse(Psr\ResponseInterface $psrResponse);
}
