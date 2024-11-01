<?php

namespace Transferito\Models\Transfer;

use Transferito\Models\Core\Config;
use ZipArchive;

class CodeBase {

    private $htaccessFile = '.htaccess';

    private $renameFileJSON = TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'renamed-files.json';

    public function createExecArchive($selectedFoldersEnabled, $folderPathArray = [])
    {
        $archive = null;

        /**
         * Create the options for the archive
         */
        $archiveOptions = [
            'stdOutFile'    => 'transferito' . DIRECTORY_SEPARATOR . '.archive-process',
            'stdErrFile'    => 'transferito' . DIRECTORY_SEPARATOR . '.archive-error',
            'filename'      => bin2hex(openssl_random_pseudo_bytes(16))
        ];

        /**
         * Check if the zip archive should be created
         */
        $isZipArchive = useZipArchive();

        /**
         * If the archive is less than the zip file limit
         * Initiate method to create zip archive
         */
        if ($isZipArchive) {
            $archive = $this->createExecZipArchive($archiveOptions, $selectedFoldersEnabled, $folderPathArray);
        }

        /**
         * If the archive is greater than the zip file limit
         * Initiate method to create tar archive
         */
        if (!$isZipArchive) {
            $archive = $this->createExecTarArchive($archiveOptions, $selectedFoldersEnabled, $folderPathArray);
        }

        /**
         * Save the PID
         */
        set_transient('transferito_codebase_archive_pid', $archive['pid']);

        return [
            'path'  => $archive['path'],
            'url'   => $archive['url']
        ];

    }

    public function createArchivePath()
    {
        try {
            $filename = bin2hex(openssl_random_pseudo_bytes(8))
                . '-'
                . date("dmY_His")
                . '-'
                . bin2hex(openssl_random_pseudo_bytes(8));

            /**
             * Check if the zip archive should be created
             */
            $isZipArchive = useZipArchive();

            /**
             * Get the correct extension based on the size changes
             */
            $extension = $isZipArchive ? 'zip' : 'tar';

            /**
             * Create the archive name with the correct extension
             */
            $archiveName = DIRECTORY_SEPARATOR . $filename . '.' . $extension;

            /**
             * Get the full path of the
             */
            $archiveFullPath = TRANSFERITO_UPLOAD_PATH . $archiveName;

            /**
             * If the archive is less than the zip file limit
             * Create an empty zip archive
             */
            if ($isZipArchive) {
                $zip = new ZipArchive();
                $zip->open($archiveFullPath, ZipArchive::CREATE);
                $zip->addFromString('transferito_tmp', '');
                $zip->close();
            }

            /**
             * If the archive is greater than the zip file limit
             * Create an empty tar archive
             */
            if (!$isZipArchive) {
                /**
                 * PharData class check
                 */
                if ($this->pharDataExists()) {
                    throw new \Exception('The PharData class does not exist and is required to create your backup.');
                }

                $tar = new \PharData($archiveFullPath, 0, null, \Phar::TAR);
                $tar->addFromString('transferito_tmp', '');
            }

            return [
                'path'  => $archiveFullPath,
                'url'   => TRANSFERITO_UPLOAD_URL . $archiveName
            ];
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function createFileList($selectedFoldersEnabled, $folderPathArray = [])
    {
        try {

            $renamedFileCount = 1;

            $settings = get_option('transferito_settings_option');
            $excludeHtaccess = !isset($settings['transferito_include_htaccess'])
                ? true
                : ($settings['transferito_include_htaccess'] === false);

            $fileIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(TRANSFERITO_ABSPATH), \RecursiveIteratorIterator::CHILD_FIRST);
            $files = [];
            $byteCount = 0;
            $fileSuffix = 1;
            $excludedFileArray = [
                TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'test.txt',
                TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'index.html',
                TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'check-public-access.php',
            ];

            /**
             * Exclude the htaccess access file
             * @todo update with the correct file name
             */
            if ($excludeHtaccess) {
                $excludedFileArray[] = ABSPATH . $this->htaccessFile;
            }

            /**
             * Create the directory to house the json files
             */
            $fileListDirectory = TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'json';
            mkdir($fileListDirectory);

            /**
             * Check if the zip archive should be created
             */
            $isZipArchive = useZipArchive();

            /**
             * Loop through the files
             */
            foreach ($fileIterator as $file) {
                if ($file->isDir()){
                    continue;
                }

                /**
                 * Assign name to variable
                 */
                $filename = $file->getPathname();

                /**
                 * Base files created, when migration has been created
                 * need to be excluded from the archive
                 */
                if (in_array($filename, $excludedFileArray)) {
                    continue;
                }

                /**
                 * Check if the folders array exists
                 */
                if ($selectedFoldersEnabled) {
                    $file = str_replace('/',"\\/", $filename);
                    $excludedArrayCheck = preg_grep('/^' . $file . '.*$/', $folderPathArray);

                    /**
                     * If the file exists in the list of excluded paths
                     */
                    if (count($excludedArrayCheck) > 0) {
                        continue;
                    }
                }

                $byteCount = $byteCount + $file->getSize();

                /**
                 * If the byte count is greater than the archive limit - split the file
                 */
                if ($byteCount >= TRANSFERITO_ZIP_LIMIT) {

                    file_put_contents($fileListDirectory . DIRECTORY_SEPARATOR . 'file_list_' . $fileSuffix . '.json', json_encode($files));

                    $byteCount = 0;
                    $fileSuffix++;

                    /**
                     * Destroy the file
                     */
                    unset($files);
                    $files = [];
                }

                /**
                 * Create array based paths
                 */
                if ($isZipArchive) {
                    $updatedPath = explode(TRANSFERITO_ABSPATH, $filename)[1];
                    $files[] = [
                        'originalPath'  => $filename,
                        'updatedPath'   => str_replace("\\", "/", $updatedPath),
                    ];
                }

                /**
                 * Create path based array
                 */
                if (!$isZipArchive) {
                    $fileKey = explode(TRANSFERITO_ABSPATH, $filename)[1];
                    $filenameOnlyArray = explode(DIRECTORY_SEPARATOR, $fileKey);
                    $filenameOnly = end($filenameOnlyArray);
                    $filenameLength = strlen($filenameOnly);

                    if ($filenameLength < 100) {
                        $files[$fileKey] = $filename;
                    } else {

                        $tempFilePath = TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . $renamedFileCount . '.txt';

                        /**
                         * Copy the file to a temp location
                         */
                        copy($filename, $tempFilePath);

                        /**
                         * Get the path name
                         */
                        $tempFileKey = explode(TRANSFERITO_ABSPATH, $tempFilePath)[1];

                        /**
                         * Assign the temp path to the file array
                         */
                        $files[$tempFileKey] = $tempFilePath;

                        /**
                         * Get the renamed file
                         */
                        $renamedFiles = $this->getRenameFileContents();

                        /**
                         * Create an array with the renamed files
                         */
                        $renamedFiles[] = [
                            'renamedFilePath'   => $tempFilePath,
                            'originalFilePath'  => $filename,
                            'count'             => $filenameLength
                        ];

                        /**
                         * Add it to the renamed files
                         */
                        $this->addToRenameFile($renamedFiles);

                        /**
                         * Increment the rename count
                         */
                        $renamedFileCount++;
                    }
                }
            }

            /**
             * Create the last file
             */
            if (count($files) > 0) {
                file_put_contents($fileListDirectory . DIRECTORY_SEPARATOR . 'file_list_' . $fileSuffix . '.json', json_encode($files));
            }

            return [
                'created'   => true,
                'amount'    => $fileSuffix
            ];

        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function addFileToArchive($archiveFilePath, $paths)
    {
        set_time_limit(0);

        try {
            /**
             * Check if the zip archive should be created
             */
            $isZipArchive = useZipArchive();

            /**
             * If the archive is less than the zip file limit
             * Add to the ZIP archive
             */
            if ($isZipArchive) {
                $zip = new ZipArchive();
                if ($zip->open($archiveFilePath) === TRUE) {
                    foreach ($paths as $path) {
                        $zip->addFile($path->originalPath, $path->updatedPath);
                    }
                    // $zip->close();
                } else {
                    throw new \Exception('We can not open your zip file.');
                }
            }

            /**
             * If the archive is greater than the zip file limit
             * Add to the TAR archive
             */
            if (!$isZipArchive) {
                /**
                 * PharData class check
                 */
                try {
                    $this->pharDataExists();
                } catch (\Exception $ex) {
                    throw new \Exception('We\'re unable to start your backup. Reason: ' . $ex->getMessage());
                }

                $array = json_decode(json_encode($paths), true);
                $tar = new \PharData($archiveFilePath);
                $tar->buildFromIterator(new \ArrayIterator($array));
            }

            return true;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    private function createExecZipArchive(array $archiveOptions, $selectedFoldersEnabled, $folderPathArray = [])
    {
        $path = substr(TRANSFERITO_ABSPATH, 0, -1);

        $archiveName = '/' . $archiveOptions['filename'] . '.zip';
        $archiveFullPath = TRANSFERITO_UPLOAD_PATH . $archiveName;
        $settings = get_option('transferito_settings_option');
        $excludeHtaccess = !isset($settings['transferito_include_htaccess'])
            ? true
            : ($settings['transferito_include_htaccess'] === false);

        /**
         * If the selected folders are enabled
         * & the folder path array has more than 1 element
         */
        if ($selectedFoldersEnabled && count($folderPathArray) > 0) {
            $folderList = implode(' ', $folderPathArray);
            $directories = str_replace(TRANSFERITO_ABSPATH, '', $folderList);
            $command = 'cd ' . $path . '; zip -1 -db -r  ' . $archiveFullPath . ' ' . $directories . ' > ' . $archiveOptions['stdOutFile'] . ' 2>' . $archiveOptions['stdErrFile'] . ' & echo $!;';
        } else {
            if (!$excludeHtaccess) {
                $command = 'cd ' . $path . '; zip -1 -x "transferito/" "transferito/**" -db -r ' . $archiveFullPath . ' . > ' . $archiveOptions['stdOutFile'] . ' 2>' . $archiveOptions['stdErrFile'] . ' & echo $!;';
            }

            if ($excludeHtaccess) {
                $command = 'cd ' . $path . '; zip -1 -x "transferito/" "transferito/**" "' . $this->htaccessFile . '" -db -r ' . $archiveFullPath . ' . > ' . $archiveOptions['stdOutFile'] . ' 2>' . $archiveOptions['stdErrFile'] . ' & echo $!;';
            }
        }

        /**
         * Get the PID
         */
        $pid = exec($command);

        return [
            'pid'   => $pid,
            'path'  => $archiveFullPath,
            'url'   => TRANSFERITO_UPLOAD_URL . $archiveName
        ];
    }

    private function createExecTarArchive(array $archiveOptions, $selectedFoldersEnabled, $folderPathArray = [])
    {
        $settings = get_option('transferito_settings_option');
        $excludeHtaccess = !isset($settings['transferito_include_htaccess'])
            ?  true
            : ($settings['transferito_include_htaccess'] === false);
        $path = substr(TRANSFERITO_ABSPATH, 0, -1);
        $baseDirectory = TRANSFERITO_ABSPATH;
        $uploadPath = str_replace(TRANSFERITO_ABSPATH, '', TRANSFERITO_UPLOAD_PATH);
        $paths = ($selectedFoldersEnabled) ? str_replace(TRANSFERITO_ABSPATH, '', implode(' ', $folderPathArray)) : './';
        $exclude = ($excludeHtaccess) ? "--exclude={$uploadPath} --exclude={$this->htaccessFile}" : "--exclude={$uploadPath}";


        $archiveName = DIRECTORY_SEPARATOR . $archiveOptions['filename'] . '.tar';
        $codebasePath = 'transferito' . $archiveName;

        /**
         * Commands
         */
        $tarCommand = "cd {$path}; tar --xform s:'^./':: {$exclude} -cvf {$codebasePath} {$paths} > {$archiveOptions['stdOutFile']} 2>{$archiveOptions['stdErrFile']} & echo $!;";

        /**
         * Check if the background command can be run
         * Then execute the full command - To create the archive
         */
        $pid = exec($tarCommand);

        return [
            'pid'   => $pid,
            'path'  => $baseDirectory . $codebasePath,
            'url'   => TRANSFERITO_UPLOAD_URL . $archiveName
        ];
    }

    private function pharDataExists() {
        try {
            $sampleTarFile = TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'sample.tar';
            $tar = new \PharData($sampleTarFile);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    private function getRenameFileContents()
    {

        if (file_exists($this->renameFileJSON)) {
            return json_decode(file_get_contents($this->renameFileJSON), true);
        }

        return array();
    }

    private function addToRenameFile($files)
    {
        file_put_contents($this->renameFileJSON, json_encode($files));
    }
}
