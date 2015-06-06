<?php
namespace Flownative\Psr7Bridge\Tests\Unit;


use Flownative\Psr7Bridge\UriTransformer;
use TYPO3\Flow\Http as FlowHttp;
use TYPO3\Flow\Tests\UnitTestCase;
use Zend\Diactoros as PsrImplementation;

/**
 * Tests for the UriTransformer
 */
class UriTransformerTest extends UnitTestCase {

	/**
	 * @var UriTransformer
	 */
	protected $uriTransformer;

	/**
	 *
	 */
	public function setUp() {
		$this->uriTransformer = new UriTransformer();
	}

	/**
	 * @return array
	 */
	public function urlProvider() {
		return array(
			array('http://www.example.com/'),
			array('http://www.example.com/index.html'),
			array('http://www.example.com/foo/bar/baz'),
			array('https://www.example.com/index.html'),
			array('http://www.example.com/foo/bar?coffee=1'),
			array('http://www.example.com/foo/bar?coffee=1&tea=1'),
			array('http://www.example.com/foo/bar?coffee=1#arabica'),
			array('https://www.example.com/foo/bar?coffee=1'),
			array('https://www.example.com:8080/foo/bar.html#sencha'),
			array('https://me@example.com/foo/bar'),
			array('https://me:123456@example.com/foo/bar')
		);
	}

	/**
	 * @param string $originalUriString
	 * @test
	 * @dataProvider urlProvider
	 */
	public function convertPsrToFlowResultsInSameUri($originalUriString) {
		$psrUri = new PsrImplementation\Uri($originalUriString);
		$flowUri = $this->uriTransformer->transformPsrToFlowUri($psrUri);

		$this->assertEquals($originalUriString, $flowUri->__toString());
	}

	/**
	 * @param string $originalUriString
	 * @test
	 * @dataProvider urlProvider
	 */
	public function convertFlowToPsrResultsInSameUri($originalUriString) {
		$flowUri = new FlowHttp\Uri($originalUriString);
		$psrUri = $this->uriTransformer->transformFlowToPsrUri($flowUri, PsrImplementation\Uri::class);

		$this->assertEquals($originalUriString, $psrUri->__toString());
	}


}
