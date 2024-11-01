<?php

/**
 * @param $path
 * @param array $data
 * @return string
 */
function loadTemplate($path, array $data) {
    ob_start();
    $fullPath = plugin_dir_path( __DIR__ ) . "src" . DIRECTORY_SEPARATOR . "Views" . DIRECTORY_SEPARATOR . $path . ".php";
    include($fullPath);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

/**
 * Pull the html path
 *
 * @param $path
 * @param $data
 *
 * @todo Modify to replace strings as template variables & merge with loadTemplate
 *
 * @return string
 */
function getHTMLPart($path, $data = []) {
	$fullPath = plugin_dir_path( __DIR__ ) . "src" . DIRECTORY_SEPARATOR . "Views" . DIRECTORY_SEPARATOR . "html" . DIRECTORY_SEPARATOR . $path . ".html.php";
	$template = file_get_contents($fullPath);

	/**
	 *
	 */
	if (count($data)) {
		$templateKeys = array_keys($data);
		$templateLiterals = array_map(function($val) { return '[' . $val . ']'; }, $templateKeys);
		$templateValues = array_values($data);
		return str_replace($templateLiterals, $templateValues, $template);
	}

	return $template;
}

function readableFileSize($bytes) {
	$maxByteAllowance = TRANSFERITO_MAX_ALLOWED;
	$size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	$factor = floor((strlen($bytes) - 1) / 3);
	$roundedAmount = number_format($bytes / pow(1024, $factor), 2);
	$byteAmount = $size[$factor];

	/**
	 * Set the size
	 */
	set_transient('transferito_size_size_in_bytes', $bytes);
	set_transient('transferito_readable_size_size_in_bytes', $roundedAmount . '' . $byteAmount);

	return [
		'amount'    => $roundedAmount,
		'factor'    => $byteAmount,
		'maxSizeExceeded' => ($bytes > $maxByteAllowance)
	];
}

function getDBSize() {
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$query = "SELECT table_schema , SUM(data_length + index_length) 'size' FROM information_schema.tables WHERE table_schema='" . DB_NAME . "' GROUP BY table_schema";
	$information = $mysqli->query($query)->fetch_assoc();
	return intval($information['size']);
}

function getDirectorySize($path) {
	$databaseSize = getDBSize();
	$totalFileAmount = 0;
    $byteTotal = 0;
    $errors = array();

	$path = realpath($path);
	if ($path !==false && $path != '' && file_exists($path)) {
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
			try {
                $byteTotal += $object->getSize();
                if (!$object->isDir()){
                    $totalFileAmount++;
                }
            } catch (\Exception $exception) {
                $errors[] = $exception->getMessage();
            }

		}
	}

	/**
	 * Total size
	 * Including the database
	 */
	$totalSize = $databaseSize + $byteTotal;

	/**
	 * Set the size
	 */
	set_transient('transferito_installation_size', [
		'amountOfFiles'      => $totalFileAmount,
		'database'           => $databaseSize,
		'codebase'           => $byteTotal,
		'totalSize'          => $totalSize,
		'databasePercentage' => round(($databaseSize / $totalSize) * 100),
		'codebasePercentage' => round(($byteTotal / $totalSize) * 100),
        'errors'             => $errors
	]);

	/**
	 *
	 */
	return readableFileSize($totalSize);
}

function buildErrorReporting($reason) {
	$tokenTransient = get_transient('transferito_migration_token');
	$timestampTransient = get_transient('transferito_migration_timestamp');
	$token = $tokenTransient ? $tokenTransient : null;
	$timestamp = $timestampTransient ? $timestampTransient : null;

	$curlInfo = curl_version();

	return [
		'pluginURL'             => site_url(),
		'pluginVersion'         => TRANSFERITO_VERSION,
		'destinationURL'        => get_transient('transferito_destination_url'),
		'siteSizeInBytes'       => get_transient('transferito_size_size_in_bytes'),
		'siteSizeReadable'      => get_transient('transferito_readable_size_size_in_bytes'),
		'phpVersion'            => phpversion(),
		'wpVersion'             => get_bloginfo('version'),
		'cURLVersion'           => $curlInfo['version'],
		'openSSLVersion'        => $curlInfo['ssl_version'],
		'failureReason'         => $reason,
		'token'                 => $token,
		'timestamp'             => $timestamp,
	];
}

function createArchiveLimit() {
	$archiveLimit = 32 * pow(1024, 2);
	$memoryLimitSplit = preg_split('/(?<=[0-9])(?=[a-z]+)/i', ini_get('memory_limit'));
	$memoryLimitInteger = intval($memoryLimitSplit[0]);

	/**
	 * Check that the memory limit exists & the array has 2 elements
	 */
	if ($memoryLimitInteger > 1 && count($memoryLimitSplit) === 2) {
		$memoryLimitSize = $memoryLimitSplit[1];
		$sizePOWMap = [ 'M' => 2, 'G' => 3 ];

		/**
		 * Map the pow to the size & use half the memory
		 */
		if ($sizePOWMap[$memoryLimitSize]) {
			$memoryLimitBytes = $memoryLimitInteger * pow(1024, $sizePOWMap[$memoryLimitSize]);
			$archiveLimit = $memoryLimitBytes / 2;
		}
	}

	return $archiveLimit;
}

function transferitoConvertToBytes(array $size) {
	$bytes = 0;
	$sizePreByteConversion = intval($size[0]);

	/**
	 * If there is only one element in the array
	 * Then return the amount as bytes
	 */
	if (count($size) === 1) {
		return $sizePreByteConversion;
	}

	/**
	 * The pow map
	 */
	$sizePOWMap = ['K' => 1, 'M' => 2, 'G' => 3 ];

    /**
     * Get the size POW index
     */
    $sizePOWIndex = $size[1];

	/**
	 * Check that the pow map exists
	 */
	if (isset($sizePOWMap[$sizePOWIndex])) {
		$bytes = $sizePreByteConversion * pow(1024, $sizePOWMap[$sizePOWIndex]);
	}

	return $bytes;
}

function checkJobHasCompleted($pid) {
	if (!$pid) {
		return true;
	}
	exec(sprintf('kill -0 %d; echo $?', $pid), $output);
	return $output[0] === '1';
}

function transferitoGetLastLine($file) {
	$line = '';
	$progressFileHandle = fopen($file, 'r');
	$cursor = -1;
	fseek($progressFileHandle, $cursor, SEEK_END);
	$char = fgetc($progressFileHandle);
	//Trim trailing newline characters in the file
	while ($char === "\n" || $char === "\r") {
		fseek($progressFileHandle, $cursor--, SEEK_END);
		$char = fgetc($progressFileHandle);
	}
	//Read until the next line of the file begins or the first newline char
	while ($char !== false && $char !== "\n" && $char !== "\r") {
		//Prepend the new character
		$line = $char . $line;
		fseek($progressFileHandle, $cursor--, SEEK_END);
		$char = fgetc($progressFileHandle);
	}
	return $line;
}

function useZipArchive() {
    $settings = get_option('transferito_settings_option');
    $forceTarArchiveCreation = isset($settings['transferito_force_tar_backup'])
        ? $settings['transferito_force_tar_backup']
        : false;

    /**
     * If this is set to true
     * We default the archive creation type to TAR & return a falsey value
     */
    if ($forceTarArchiveCreation) {
        set_transient('transferito_archive_extension', 'tar');
        return false;
    }

    /**
     * If the tar archive creation hasn't been forced
     * Check see if we've reached the ZIP limit
     */
    $sizeInformation = get_transient('transferito_installation_size');
    $isZipArchive = $sizeInformation['totalSize'] < TRANSFERITO_ZIP_FILE_LIMIT;
    $extension = $isZipArchive ? 'zip' : 'tar';

    set_transient('transferito_archive_extension', $extension);

    return $isZipArchive;
}

function getObjectCacheFilterData() {
    return [
        'text'      => "apply_filters( 'enable_loading_object_cache_dropin', false );",
        'path'      => get_template_directory() . DIRECTORY_SEPARATOR . 'functions.php'
    ];
}

function getPrependOptionNameData() {
    return [
        'text'      => 'auto_prepend_file',
        'commented' => '; auto_prepend_file',
        'path'      => ABSPATH . '.user.ini'
    ];
}
