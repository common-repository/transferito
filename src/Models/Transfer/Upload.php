<?php

namespace Transferito\Models\Transfer;

use Aws\S3\S3Client;
use Aws\Credentials\Credentials;

class Upload {

	private $s3Client;

	public function __construct()
	{
		/**
		 * Create instance of the S3 Client
		 */
		$this->s3Client = new S3Client([
			'version'       => 'latest',
			'region'        => 'eu-west-2',
			'credentials'   => new Credentials(TRANSFERITO_AWS_ACCESS, TRANSFERITO_AWS_SECRET)
		]);
	}

	public function startUpload()
	{
		try {

            /**
             * Get the extension
             */
            $archiveExtension = get_transient('transferito_archive_extension');

			/**
			 * Create the name of the archive name
			 */
			$archiveName = date('gymsdi') . bin2hex(openssl_random_pseudo_bytes(64)) . '.' . $archiveExtension;

			/**
			 * Create the upload request
			 */
			$multipartCreated = $this->s3Client->createMultipartUpload([
				'Bucket'        => TRANSFERITO_AWS_BUCKET,
				'Key'           => $archiveName,
				'StorageClass'  => 'STANDARD',
				'ACL'           => 'public-read'
			]);

			/**
			 * Assign the upload information to a variable
			 */
			$uploadInfo = [
				'uploadId'  => $multipartCreated['UploadId'],
				'filename'  => $archiveName,
				'parts'     => []
			];

			/**
			 * Set the initial transient with the upload information
			 */
			set_transient('transferito_upload_information', $uploadInfo);

			return $uploadInfo['uploadId'];
		} catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
		}
	}

	public function uploadChunk($partNumber, $chunk)
	{
		/**
		 * Pull the upload info
		 */
		$uploadInfo = get_transient('transferito_upload_information');

		try {
			/**
			 * Upload the chunk
			 */
			$chunkUpload = $this->s3Client->uploadPart([
				'Bucket'     => TRANSFERITO_AWS_BUCKET,
				'Key'        => $uploadInfo['filename'],
				'UploadId'   => $uploadInfo['uploadId'],
				'PartNumber' => $partNumber,
				'Body'       => $chunk,
			]);

			/**
			 * Create the parts
			 */
			$uploadInfo['parts'][$partNumber] = [
				'PartNumber'    => $partNumber,
				'ETag'          => trim($chunkUpload['ETag'], '"')
			];

			/**
			 * Update the initial transient with the upload information
			 */
			set_transient('transferito_upload_information', $uploadInfo);

			return true;

		}  catch (\Exception $exception) {
			/**
			 * Cancel the upload
			 */
			$this->s3Client->abortMultipartUpload([
				'Bucket'    => TRANSFERITO_AWS_BUCKET,
				'Key'       => $uploadInfo['filename'],
				'UploadId'  => $uploadInfo['uploadId']
			]);

            throw new \Exception($exception->getMessage());
		}
	}

	public function completeUpload()
	{
		try {
			/**
			 * Pull the upload info
			 */
			$uploadInfo = get_transient('transferito_upload_information');

			/**
			 * Call the API to complete the upload
			 */
			$this->s3Client->completeMultipartUpload([
				'Bucket'            => TRANSFERITO_AWS_BUCKET,
				'Key'               => $uploadInfo['filename'],
				'UploadId'          => $uploadInfo['uploadId'],
				'MultipartUpload'   => [
					'Parts'         => $uploadInfo['parts']
				]
			]);

			return [
				'URL'   => TRANSFERITO_AWS_BASE_URL . $uploadInfo['filename'],
				'path'  => $uploadInfo['filename']
			];
		}  catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
		}
	}

}
