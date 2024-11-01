<?php

namespace Transferito\Models\Core;

class Config {

    public static function getBasePath()
    {
        return TRANSFERITO_UPLOAD_PATH;
    }

    public static function getChunkSize()
    {
        return TRANSFERITO_CHUNK_SIZE;
    }

    public static function getUploadPath()
    {
        return wp_upload_dir()["path"];
    }

	public static function getBaseApiUrl()
	{
        return 'https://api.transferito.com/wp';
    }

	public static function getEndpoint($endpoint)
	{
		return self::getBaseApiUrl() . '/' . $endpoint;
    }

    public static function getCorrectPath()
    {
        $transferitoDir = self::getBasePath();

	    /**
	     * PHP File to create
	     */
	    $json = "<?php" . PHP_EOL;
	    $json .= 'header("Content-type: application/json;charset=utf-8");' .PHP_EOL;
	    $json .= 'http_response_code(200);' .PHP_EOL;
	    $json .= "echo json_encode([ 'canAccess' => true ]);" .PHP_EOL;
	    $json .= "die();" .PHP_EOL;

	    /**
	     *
	     */
        if (!file_exists($transferitoDir)) {
            if (!mkdir($transferitoDir, 0755)) {
                $transferitoDir = self::getUploadPath();
            }
        }

	    file_put_contents($transferitoDir . DIRECTORY_SEPARATOR . 'index.html', '');
	    file_put_contents($transferitoDir . DIRECTORY_SEPARATOR . 'check-public-access.php', $json);

        return $transferitoDir;
    }

    public static function createTestFile()
    {
        $transferitoDir = self::getBasePath();
        $transferitoHash = bin2hex(openssl_random_pseudo_bytes(32));
        $filename = 'test.txt';

	    /**
	     * As long as the directory exists
	     * Create the test file
	     */
        if (file_exists($transferitoDir)) {
            file_put_contents($transferitoDir . DIRECTORY_SEPARATOR . $filename, 'Transferito Hash: ' . $transferitoHash);
        }

        return [
        	'url'   => site_url() . '/transferito/' . $filename,
        	'hash'  => $transferitoHash,
        ];
    }

	public static function getWPContentPaths()
	{
		$paths = [];
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(WP_CONTENT_DIR, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST);
		$iterator->setMaxDepth(1);
		foreach($iterator as $file) {
			if($file->isDir()) {
				$depth = $iterator->getDepth();
				if ($depth === 0) {
					$paths[$file->getFilename()] = [];
				} else if ($depth === 1) {
					$fullPath = $file->getRealpath();					
					$splitPaths = array_filter(explode(DIRECTORY_SEPARATOR, substr($fullPath, strlen(WP_CONTENT_DIR))));

                    /**
                     * Check if index 1 & 2 exist
                     */
                    $directoryLevel1Path = isset($splitPaths[1]) ? $splitPaths[1] : false;
                    $directoryLevel2Path = isset($splitPaths[2]) ? $splitPaths[2] : false;

                    /**
                     * Only push to array if directory paths exist
                     */
                    if ($directoryLevel1Path && $directoryLevel2Path) {
                        array_push($paths[$directoryLevel1Path], $directoryLevel2Path);
                    }
				}
			}
		}
		return $paths;
    }
}
