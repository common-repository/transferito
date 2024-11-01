<?php

namespace Transferito\Models\Core;

use Transferito\Models\Core\Config;

class Api {

	private $savedAPI;
	private $planDetail;
	private $statusUrl;
	private $createMigrationFreeURL;
	private $createMigrationPaidURL;
	private $startMigrationFreeURL;
	private $startMigrationPaidURL;
	private $directDownloadCheckURL;
	private $envCheckURL;
	private $getDirectoriesURL;
	private $completeUploadURL;
	private $sslCheckURL;
	private $errorReportingURL;
	private $serverCheckURL;
	private $cPanelAuthURL;
	private $ftpConnectionTestURL;
	private $directoryTestURL;
	private $dbConnectionTestURL;
	private $hostingGuideRequestURL;
	private $telemetryURL;
	private $directoryCheckURL;

    private $directoryCheckStatusURL;

	/**
	 * Keys
	 */
	private $publicKey;
	private $secretKey;

	private $freeUser;
	private $maxSizeExceeded;

	public function __construct()
	{
		$settings = get_option('transferito_settings_option');
		$this->savedAPI = Config::getEndpoint('information');
		$this->planDetail = Config::getEndpoint('detail');
		$this->statusUrl = Config::getEndpoint('status');
		$this->createMigrationFreeURL = Config::getEndpoint('free-migration/create');
		$this->createMigrationPaidURL = Config::getEndpoint('paid-migration/create');
		$this->startMigrationFreeURL = Config::getEndpoint('free-migration/start');
		$this->startMigrationPaidURL = Config::getEndpoint('paid-migration/start');
        $this->directDownloadCheckURL = Config::getEndpoint('download/check');
		$this->envCheckURL = Config::getEndpoint('environment-check');

        /**
         * @deprecated To be removed in future releases
         */
		$this->getDirectoriesURL = Config::getEndpoint('ftp/directories');

		$this->completeUploadURL = Config::getEndpoint('upload/complete');
		$this->sslCheckURL = Config::getEndpoint('ssl/check');
		$this->errorReportingURL = Config::getEndpoint('error/reporting');
		$this->serverCheckURL = Config::getEndpoint('server/requirements');
		$this->cPanelAuthURL = Config::getEndpoint('domain/check');
		$this->ftpConnectionTestURL = Config::getEndpoint('test/ftp-both');

        /**
         * @deprecated To be removed in future releases
         */
		$this->directoryTestURL = Config::getEndpoint('test/directory');

		$this->dbConnectionTestURL = Config::getEndpoint('test/database');
		$this->hostingGuideRequestURL = Config::getEndpoint('request/hosting-guide');
		$this->telemetryURL = Config::getEndpoint('telemetry');
        $this->directoryCheckURL = Config::getEndpoint('directory-check');
        $this->directoryCheckStatusURL = Config::getEndpoint('directory-check/status');

		/**
		 * API Keys
		 */
		$publicKey = isset($settings['public_transferito_key']) ? $settings['public_transferito_key'] : '';
		$secretKey = isset($settings['secret_transferito_key']) ? $settings['secret_transferito_key'] : '';
		$this->publicKey = sanitize_text_field($publicKey);
		$this->secretKey = sanitize_text_field($secretKey);

		/**
		 * If user doesn't have API keys set as a free user
		 */
		$this->freeUser = !($this->publicKey && $this->secretKey);
	}

	public function setFreeUser()
	{
		$this->freeUser = true;
	}

	public function setMaxSizeExceeded($maxSizeExceeded)
	{
		$this->maxSizeExceeded = $maxSizeExceeded;
	}

	public function createMigration(array $transferDetail)
	{
		$migrationUrl = $this->freeUser ? $this->createMigrationFreeURL : $this->createMigrationPaidURL;
		return $this->post($migrationUrl, $transferDetail);
	}

	public function startMigration(array $transferDetail)
	{
		$migrationUrl = $this->freeUser ? $this->startMigrationFreeURL : $this->startMigrationPaidURL;
		return $this->post($migrationUrl, $transferDetail);
	}

	public function getStatus($token)
	{
		$transferStatus = $this->statusUrl . '/' . $token;
		return $this->get($transferStatus);
	}

	public function planInformation()
	{
		return $this->post($this->planDetail, [], true);
	}

	public function canFindSite()
	{
		return $this->post($this->envCheckURL, [ 'url' => TRANSFERITO_UPLOAD_URL ], true);
	}

    public function directDownloadCheck(array $migrationInfo)
    {
        return $this->post($this->directDownloadCheckURL, $migrationInfo, true);
    }

	public function sslCheck()
	{
		return $this->get($this->sslCheckURL, true);
	}

	public function failedMigration($reason)
	{
		$errorInformation = buildErrorReporting($reason);
		return $this->post($this->errorReportingURL, $errorInformation, true);
	}

	public function checkDestinationServerRequirements($migrationInfo)
	{
		return $this->post($this->serverCheckURL, $migrationInfo, true);
	}

	public function cPanelAuth(array $cPanelAuthInfo)
	{
		return $this->post($this->cPanelAuthURL, $cPanelAuthInfo, true);
	}

	public function ftpValidation(array $serverDetails)
	{
		return $this->post($this->ftpConnectionTestURL, $serverDetails);
	}

	public function directoryCheck(array $serverDetails)
	{
		return $this->post($this->directoryTestURL, $serverDetails, true);
	}

    public function startDirectoryCheck(array $serverDetails)
    {
        return $this->post($this->directoryCheckURL, $serverDetails);
    }


    public function getDirectoryUpdate(array $directoryCheckDetails)
    {
        return $this->post($this->directoryCheckStatusURL, $directoryCheckDetails);
    }

	public function databaseValidation(array $databaseDetails)
	{
		return $this->post($this->dbConnectionTestURL, $databaseDetails);
	}

	public function completeUpload(array $uploadInfo)
	{
		return $this->post($this->completeUploadURL, $uploadInfo, true);
	}

    public function hostingGuideRequest(array $guideRequestInfo) {
        return $this->post($this->hostingGuideRequestURL, $guideRequestInfo);
    }

    public function pushTelemetry(array $telemetry) {
        return $this->post($this->telemetryURL, $telemetry);
    }

	public function getSavedDetail()
	{
		$result = array(
			'savedFTP'  => [],
			'savedDBS'  => []
		);

		/**
		 * Call api - to get saved details
		 */
		$savedDetail = $this->post($this->savedAPI, array());

		/**
		 * If the response is success
		 * Then create the result array
		 */
		if ($savedDetail['code'] === 200) {
			$response = $savedDetail['message'];
			$result = array(
				'savedFTP'  => isset($response->newFTP) ? $response->newFTP : [],
				'savedDBs'  => isset($response->database) ? $response->database : []
			);
		}

		return $result;
	}

	public function getDirectories(array $ftpDetails)
	{
		return $this->post($this->getDirectoriesURL, $ftpDetails);
	}

	public function cPanelAvailabilityCheck($domain)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $domain);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15); //timeout in seconds

		$result = curl_exec($ch);

		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		return array(
			'message'   => $result,
			'url'       => $url,
			'code'      => $httpCode
		);
	}

	private function getRequestHeaders()
	{
		return array(
			'pKey'      => $this->publicKey,
			'sKey'      => $this->secretKey,
			'platform'  => 'wp_plugin',
			'token'     => wp_create_nonce('wp_plugin')
		);
	}

	private function stringifyHeaders($additionalHeaders = [])
	{
		$headers = array_merge($this->getRequestHeaders(), $additionalHeaders);
		$updatedHeaders = [];
		foreach ($headers as $key => $headerValue) {
			$updatedHeaders[] = $key . ':' . $headerValue;
		}
		return $updatedHeaders;
	}

	private function post($url, $body, $returnResultProperty = false)
	{
		$useFallback = get_transient('transferito_request_fallback');

		/**
		 * Default to use PHP cURL
		 */
		if ($useFallback) {
			return $this->curlRequest($url, $returnResultProperty, $body, 'POST');
		} else {
			/**
			 * Call the endpoint
			 */
			$response = wp_remote_post($url, array(
				'method'    => 'POST',
				'timeout'   => 15000,
				'blocking'  => true,
				'body'      => $body,
				'headers'   => $this->getRequestHeaders()
			));
			return $this->handleWPResponse($response, $returnResultProperty);
		}
	}

	private function get($url, $returnResultProperty = false, $rawResult = false)
	{
		$useFallback = get_transient('transferito_request_fallback');

		/**
		 * Default to use PHP cURL
		 */
		if ($useFallback) {
			return $this->curlRequest($url, $returnResultProperty);
		} else {
			$response = wp_remote_get($url, array(
				'headers'   => $this->getRequestHeaders()
			));
			return $this->handleWPResponse($response, $returnResultProperty, $rawResult);
		}
	}

	private function handleWPResponse($response, $returnResultProperty = false, $rawResult = false)
	{
		/**
		 *
		 */
		$httpCode = wp_remote_retrieve_response_code($response);

		/**
		 * Check on the result of the transaction
		 */
		if (is_wp_error($response)) {
			$responseMessage = $response->get_error_message();
		} else {
			$result = wp_remote_retrieve_body($response);
			$jsonResponse = json_decode($result);
			$responseMessage = ($returnResultProperty) ? $jsonResponse->result : $jsonResponse;

			if ($rawResult) {
				$responseMessage = $result;
			}
		}

		// close the session
		return array(
			'message'   => $responseMessage,
			'code'      => $httpCode,
		);
	}

	private function curlRequest($url, $returnResultProperty = false, $body = [], $method = 'GET')
	{
		$request = curl_init($url);

		if ($method === 'POST') {
			curl_setopt($request, CURLOPT_POST, true);
			curl_setopt($request, CURLOPT_POSTFIELDS, $body);
		}

		curl_setopt($request, CURLOPT_HTTPHEADER, $this->stringifyHeaders());
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request, CURLOPT_SSLVERSION, 3);

		$result = curl_exec($request);
		$httpCode = curl_getinfo($request, CURLINFO_HTTP_CODE);

		// close the session
		curl_close($request);

		return $this->handleFallbackResponse($httpCode, $result, $returnResultProperty);
	}

	private function handleFallbackResponse($httpCode, $response, $returnResultProperty = false)
	{
		/**
		 * Check on the result of the transaction
		 */
		$jsonResponse = json_decode($response);
		$responseMessage = ($returnResultProperty) ? $jsonResponse->result : $jsonResponse;

		// Return formatted response
		return array(
			'message'   => $responseMessage,
			'code'      => $httpCode,
		);
	}

}
