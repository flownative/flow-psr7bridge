<?php
namespace Flownative\Psr7Bridge\Tests\Unit;

use Flownative\Psr7Bridge\RequestTransformerInterface;
use Flownative\Psr7Bridge\UriTransformer;
use Flownative\Psr7Bridge\RequestTransformer;
use Flownative\Psr7Bridge\UriTransformerInterface;
use TYPO3\Flow\Http as FlowHttp;
use TYPO3\Flow\Tests\UnitTestCase;
use Zend\Diactoros as PsrImplementation;
use Psr\Http\Message as Psr;
use org\bovigo\vfs\vfsStream;

/**
 * Tests for the RequestTransformer
 */
class RequestTransformerTest extends UnitTestCase {

	/**
	 * @var UriTransformerInterface
	 */
	protected $uriTransformerMock;

	/**
	 * @var string
	 */
	protected $testingUri = 'http://localhost/index.html?foo=bar';

	/**
	 * @var RequestTransformerInterface
	 */
	protected $requestTransformer;

	/**
	 *
	 */
	public function setUp() {
		vfsStream::setup('Test');
		$this->uriTransformerMock = $this->getMock(UriTransformer::class, [], [], '', FALSE);
		$this->uriTransformerMock->expects($this->any())->method('transformPsrToFlowUri')->willReturn(new FlowHttp\Uri($this->testingUri));
		$this->uriTransformerMock->expects($this->any())->method('transformFlowToPsrUri')->willReturn(new PsrImplementation\Uri($this->testingUri));
		$this->requestTransformer = new RequestTransformer($this->uriTransformerMock, PsrImplementation\Request::class, function ($resource) {
			return new PsrImplementation\Stream($resource);
		});
	}

	/**
	 * @test
	 */
	public function convertPsrToFlowResultsInSameRequest() {
		$requestContentString = 'coffee=1';
		file_put_contents('vfs://Test/requestContent.txt', $requestContentString);
		$method = 'POST';


		$psrRequest = new PsrImplementation\Request($this->testingUri, $method, fopen('vfs://Test/requestContent.txt', 'rb'), ['X-Test' => 'single value', 'X-Another-Test' => ['value1', 'value2']]);

		$flowRequest = $this->requestTransformer->transformPsrToFlowRequest($psrRequest);

		$this->assertEquals($this->testingUri, $flowRequest->getUri()->__toString());
		$this->assertEquals($requestContentString, $flowRequest->getContent());
		$this->assertEquals($method, $flowRequest->getMethod());

 		$flowRequestHeadersArray = $flowRequest->getHeaders()->getAll();
		$this->assertArrayHasKey('X-Test', $flowRequestHeadersArray);
		$this->assertCount(1, $flowRequestHeadersArray['X-Test']);
		$this->assertArrayHasKey('X-Another-Test', $flowRequestHeadersArray);
		$this->assertCount(2, $flowRequestHeadersArray['X-Another-Test']);
	}

	/**
	 * @test
	 */
	public function convertFlowToPsrResultsInSameRequest() {
		$requestContentString = 'coffee=1';
		file_put_contents('vfs://Test/requestContent.txt', $requestContentString);
		$method = 'POST';

		$flowUri = new FlowHttp\Uri($this->testingUri);
		$flowRequest = FlowHttp\Request::create($flowUri, $method);
		$flowRequest->setContent($requestContentString);
		$flowRequest->setHeader('X-Test', 'single value');
		$flowRequest->setHeader('X-Another-Test', ['value1', 'value2']);

		$psrRequest = $this->requestTransformer->transformFlowToPsrRequest($flowRequest);

		$this->assertEquals($this->testingUri, $psrRequest->getUri()->__toString());
		$this->assertEquals($requestContentString, $psrRequest->getBody()->__toString());
		$this->assertEquals($method, $psrRequest->getMethod());

		$psrRequestHeadersArray = $psrRequest->getHeaders();
		$this->assertArrayHasKey('X-Test', $psrRequestHeadersArray);
		$this->assertCount(1, $psrRequestHeadersArray['X-Test']);
		$this->assertArrayHasKey('X-Another-Test', $psrRequestHeadersArray);
		$this->assertCount(2, $psrRequestHeadersArray['X-Another-Test']);
	}

	/**
	 * @test
	 */
	public function createPsrStreamFromFlowRequestReturnsStreamWithCorrectContentIfContentIsString() {
		$requestContentString = 'Request Content is good!';
		/** @var FlowHttp\Request $flowRequest */
		$flowRequest = $this->getMock(FlowHttp\Request::class, NULL, [], '', FALSE);
		$flowRequest->setContent($requestContentString);

		$psrStream = $this->requestTransformer->createPsrStreamFromFlowRequest($flowRequest);

		$this->assertInstanceOf(Psr\StreamInterface::class, $psrStream);
		$this->assertEquals($requestContentString, $psrStream->__toString());
	}

	/**
	 * @test
	 */
	public function createPsrStreamFromFlowRequestReturnsStreamWithCorrectContentIfContentIsResource() {
		$requestContentString = 'Request Content is good!';
		file_put_contents('vfs://Test/requestContent.txt', $requestContentString);

		/** @var FlowHttp\Request $flowRequest */
		$flowRequest = $this->getMock(FlowHttp\Request::class, NULL, [], '', FALSE);
		// Due to https://jira.typo3.org/browse/FLOW-304 the inputStreamUri needs to be set like this for testing.
		$this->inject($flowRequest, 'inputStreamUri', 'vfs://Test/requestContent.txt');

		$psrStream = $this->requestTransformer->createPsrStreamFromFlowRequest($flowRequest);

		$this->assertInstanceOf(Psr\StreamInterface::class, $psrStream);
		$this->assertEquals($requestContentString, $psrStream->getContents());
	}

}
