<?php
namespace Flownative\Psr7Bridge;

use TYPO3\Flow\Http as FlowHttp;
use Psr\Http\Message as Psr;
use TYPO3\Flow\Utility\Arrays;

/**
 * Transformer to convert \TYPO3\Flow\Http\Request instances to \Psr\Http\Message\ServerRequestInterface implementation instances and vice versa.
 * Additionally can extract a \Psr\Http\Message\StreamInterface instance from the Flow Request content.
 */
class ServerRequestTransformer extends RequestTransformer implements ServerRequestTransformerInterface {

	/**
	 * The UriTransformer instance to use
	 *
	 * @var UriTransformerInterface
	 */
	protected $uriTransformer;

	/**
	 * A function that accepts one argument which will be a PHP resource stream and returns a PSR-7 StreamInterface instance wrapping the given resource.
	 *
	 * @var \Closure
	 */
	protected $streamConstructorClosure;

	/**
	 *  A closure that should accept an array with typical $_FILES keys for a single uploaded file (keys: "tmp_name", "name", "size", "error", "type") and should return an instance of UploadedFileInterface
	 *
	 * @var \Closure
	 */
	protected $uploadedFileConstructorClosure;

	/**
	 * @param UriTransformerInterface $uriTransformer
	 * @param string $psrServerRequestImplementationClassName The PSR-7 RequestInterface implementation class to convert to.
	 * @param \Closure $streamContructorClosure A closure that should accept a PHP resource stream and return a PSR-7 StreamInterface instance wrapping the resource.
	 * @param \Closure $uploadedFileConstructorClosure A closure that should accept an array with typical $_FILES keys for a single uploaded file (keys: "tmp_name", "name", "size", "error", "type") and should return an instance of UploadedFileInterface
	 */
	public function __construct(UriTransformerInterface $uriTransformer, $psrServerRequestImplementationClassName, \Closure $streamContructorClosure, \Closure $uploadedFileConstructorClosure) {
		$this->uriTransformer = $uriTransformer;
		$this->psrRequestImplementationClassName = $psrServerRequestImplementationClassName;
		$this->streamConstructorClosure = $streamContructorClosure;
		$this->uploadedFileConstructorClosure = $uploadedFileConstructorClosure;
	}

	/**
	 * Takes a Flow HTTP request and transforms it into a \Psr\Http\Message\ServerRequestInterface implementation instance.
	 *
	 * @param FlowHttp\Request $flowRequest The Flow Request to transform
	 * @return Psr\ServerRequestInterface
	 */
	public function transformFlowToPsrRequest(FlowHttp\Request $flowRequest) {
		/** @var Psr\ServerRequestInterface $psrRequest */
		$psrRequest = parent::transformFlowToPsrRequest($flowRequest);

		$explodedArguments = $this->explodeFlowRequestArguments($flowRequest);

		$psrRequest = $psrRequest->withQueryParams($explodedArguments['GET'])
			->withUploadedFiles($explodedArguments['FILES'])
			->withCookieParams($explodedArguments['COOKIES']);

		if ($flowRequest->getMethod() === 'POST') {
			$psrRequest = $psrRequest->withParsedBody($explodedArguments['POST']);
		}

		return $psrRequest;
	}

	/**
	 * Takes a \Psr\Http\Message\ServerRequestInterface implementation instance and transforms it to a \TYPO3\Flow\Http\Request instance.
	 *
	 * @param Psr\ServerRequestInterface $psrRequest The PSR-7 request to convert
	 * @return FlowHttp\Request
	 */
	public function transformPsrToFlowRequest(Psr\ServerRequestInterface $psrRequest) {
		$flowUri = $this->uriTransformer->transformPsrToFlowUri($psrRequest->getUri());
		$method = $psrRequest->getMethod();
		$body = $psrRequest->getBody();
		$arguments = $psrRequest->getParsedBody();
		$server = $psrRequest->getServerParams();

		// TODO: Disabled as it seems difficult to transfer upload file data, see transformUploadedFileInstanceToUploadArray()
		// $files = $this->transformUploadedFileInstanceToUploadArray($psrRequest->getUploadedFiles());
		// $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $files);

		$flowRequest = FlowHttp\Request::create($flowUri, $method, $arguments, [], $server);
		$flowRequest->setContent($body->getContents());
		$flowRequest->setVersion('HTTP/' . $psrRequest->getProtocolVersion());
		foreach ($psrRequest->getHeaders() as $headerName => $values) {
			$flowRequest->setHeader($headerName, $values);
		}

		return $flowRequest;
	}

	/**
	 * Tries to explode the merged request arguments of flow into separate information. And gets cookie data.
	 *
	 * @param FlowHttp\Request $flowRequest
	 * @return array
	 */
	protected function explodeFlowRequestArguments(FlowHttp\Request $flowRequest) {
		$uriQueryString = $flowRequest->getUri()->getQuery();
		$uriQueryArguments = $getArguments = $files = $cookies = [];
		parse_str($uriQueryString, $uriQueryArguments);


		$flowArgumentsArray = $flowRequest->getArguments();
		foreach (array_keys($uriQueryArguments) as $argumentName) {
			if (isset($flowArgumentsArray[$argumentName])) {
				$getArguments[$argumentName] = $flowArgumentsArray[$argumentName];
				unset($flowArgumentsArray[$argumentName]);
			}
		}

		$this->traverseArgumentsAndFindUploadedFiles($flowArgumentsArray, $uploadedFiles);
		foreach ($uploadedFiles as $uploadPath => $uploadInfo) {
			Arrays::setValueByPath($files, trim($uploadPath, '.'), $this->transformUploadInfoToUploadFileInstance($uploadInfo));
		}

		/** @var FlowHttp\Cookie $cookie */
		foreach ($flowRequest->getCookies() as $cookie) {
			$cookies[$cookie->getName()] = $cookie->getValue();
		}

		return [
			'GET' => $getArguments,
			'POST' => $flowArgumentsArray,
			'FILES' => $files,
			'COOKIES' => $cookies
		];
	}

	/**
	 * @param array $uploadedFilesArray
	 */
	protected function replaceUploadedFileInstancesWithUploadInformation(array &$uploadedFilesArray) {
		foreach ($uploadedFilesArray as $key => &$value) {
			if ($value instanceof Psr\UploadedFileInterface) {
				$uploadedFilesArray[$key] = $this->transformUploadedFileInstanceToUploadArray($value);
			} elseif (is_array($value)) {
				$this->replaceUploadedFileInstancesWithUploadInformation($value);
			} else {
				unset($uploadedFilesArray[$key]);
			}
		}
	}

	/**
	 * @param Psr\UploadedFileInterface $uploadedFileInstance
	 * @return array
	 */
	protected function transformUploadedFileInstanceToUploadArray(Psr\UploadedFileInterface $uploadedFileInstance) {
		// This seems impossible as UploadedFileInterface allows no direct access to the upload location and flow expects the original upload location to call "move_uploaded_file" on it.
		return [];
	}

	/**
	 * @return Psr\ServerRequestInterface
	 */
	protected function createPsrRequestInstance() {
		return new $this->psrRequestImplementationClassName;
	}


	/**
	 * @param array $uploadInfo
	 * @return Psr\UploadedFileInterface
	 */
	protected function transformUploadInfoToUploadFileInstance($uploadInfo) {
		return $this->uploadedFileConstructorClosure->__invoke($uploadInfo);
	}

	/**
	 * Tries to find uploaded file data in the arguments array and extracts it from there.
	 *
	 * @param array $arguments
	 * @param array $uploadedFiles
	 * @param string $currentPath
	 */
	protected function traverseArgumentsAndFindUploadedFiles(array &$arguments, &$uploadedFiles = [], $currentPath = '') {
		foreach ($arguments as $argumentName => &$argumentValue) {
			$argumentPath = $currentPath . '.' . $argumentName;
			if ($this->isUploadedFile($argumentValue)) {
				$uploadedFiles[$argumentPath] = $argumentValue;
				unset($arguments[$argumentName]);
			} elseif (is_array($argumentValue)) {
				$this->traverseArgumentsAndFindUploadedFiles($argumentValue, $uploadedFiles, $argumentPath);
			}
		}
	}

	/**
	 * Weak check if an argument value (array) might be uploaded file information.
	 *
	 * @param mixed $argumentValue
	 * @return boolean
	 */
	protected function isUploadedFile($argumentValue) {
		if (!is_array($argumentValue)) {
			return FALSE;
		}

		if (!isset($argumentValue['tmp_name'])) {
			return FALSE;
		}

		if (!isset($argumentValue['name'])) {
			return FALSE;
		}

		foreach ($argumentValue as $subValue) {
			if (is_array($subValue)) {
				return FALSE;
			}
		}

		return TRUE;
	}
}
