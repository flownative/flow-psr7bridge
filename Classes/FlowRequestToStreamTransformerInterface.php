<?php
namespace Flownative\Psr7Bridge;

use Psr\Http\Message as Psr;
use TYPO3\Flow\Http as FlowHttp;


interface FlowRequestToStreamTransformerInterface {

	/**
	 * Extracts a \Psr\Http\Message\StreamInterface instance from the body of a Flow request. As instanciation of the Stream can depend
	 * on the implementation you have to hand in a closure that can create the stream instance from a given PHP resource.
	 *
	 * @param FlowHttp\Request $request The Flow HTTP request to extract the content from
	 * @return Psr\StreamInterface
	 */
	public function createPsrStreamFromFlowRequest(FlowHttp\Request $request);
}