<?php
namespace Flownative\Psr7Bridge\Tests\Unit;


use Flownative\Psr7Bridge\ResponseTransformer;
use Flownative\Psr7Bridge\ResponseTransformerInterface;
use TYPO3\Flow\Http as FlowHttp;
use TYPO3\Flow\Tests\UnitTestCase;
use Zend\Diactoros as PsrImplementation;
use Psr\Http\Message as Psr;
use org\bovigo\vfs\vfsStream;

/**
 * Tests for the ResponseTransformer
 */
class ResponseTransformerTest extends UnitTestCase {

	/**
	 * @var ResponseTransformerInterface
	 */
	protected $responseTransformer;

	/**
	 *
	 */
	public function setUp() {
		vfsStream::setup('Test');
		$this->responseTransformer = new ResponseTransformer(PsrImplementation\Response::class, function ($content) {
			$resource = fopen('php://temp', 'rw');
			fwrite($resource, $content);
			return new PsrImplementation\Stream($resource);
		});
	}

	/**
	 * @test
	 */
	public function convertPsrToFlowResultsInSameResponse() {
		$responseContentString = 'Wonderful response :)';
		file_put_contents('vfs://Test/responseContent.txt', $responseContentString);

		$resource = fopen('vfs://Test/responseContent.txt', 'c+');
		$psrStream = new PsrImplementation\Stream($resource);
		$psrResponse = new PsrImplementation\Response($psrStream, 200, ['Content-Type' => 'text/plain', 'X-Test' => ['foo', 'bar']]);

		$flowResponse = $this->responseTransformer->transformPsrToFlowResponse($psrResponse);

		$this->assertEquals(200, $flowResponse->getStatusCode());
		$this->assertEquals($responseContentString, $flowResponse->getContent());

 		$flowResponseHeadersArray = $flowResponse->getHeaders()->getAll();
		$this->assertArrayHasKey('Content-Type', $flowResponseHeadersArray);
		$this->assertSame('text/plain; charset=UTF-8', $flowResponseHeadersArray['Content-Type'][0]);
		$this->assertArrayHasKey('X-Test', $flowResponseHeadersArray);
		$this->assertCount(2, $flowResponseHeadersArray['X-Test']);
	}

	/**
	 * @test
	 */
	public function convertFlowToPsrResultsInSameResponse() {
		$responseContentString = 'Wonderful response :)';
		file_put_contents('vfs://Test/responseContent.txt', $responseContentString);

		$flowResponse = new FlowHttp\Response();
		$flowResponse->setContent($responseContentString);
		// Note Flow will append charset to "text/*" Content-Types!
		$flowResponse->setHeader('Content-Type', 'text/plain; charset=UTF-8');
		$flowResponse->setHeader('X-Test', ['foo', 'bar']);

		$psrResponse = $this->responseTransformer->transformFlowToPsrResponse($flowResponse);

		$this->assertEquals(200, $psrResponse->getStatusCode());
		$this->assertEquals($responseContentString, $psrResponse->getBody()->__toString());

		$psrRequestHeadersArray = $psrResponse->getHeaders();
		$this->assertArrayHasKey('Content-Type', $psrRequestHeadersArray);
		$this->assertSame('text/plain; charset=UTF-8', $psrRequestHeadersArray['Content-Type'][0]);
		$this->assertArrayHasKey('X-Test', $psrRequestHeadersArray);
		$this->assertCount(2, $psrRequestHeadersArray['X-Test']);
	}
}
