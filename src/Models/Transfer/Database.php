<?php

namespace Transferito\Models\Transfer;

use mysqli;
use mysqli_driver;
use mysqli_sql_exception;


class Database {

    private $collationTypes = array(
        'column'    => 'column',
        'table'     => 'table'
    );

    private function removeCollation($createTableSQL, $type)
    {
        if (!in_array($type, array_values($this->collationTypes))) {
            return $createTableSQL;
        }

        $regExpPatterns = array(
            'column'    => '/( COLLATE)\s(.*?)(?=\s|,)/',
            'table'     => '/( COLLATE)=(.*?)$/'
        );

        $updatedSQL = preg_replace($regExpPatterns[$type], '', $createTableSQL);
        return $updatedSQL;
    }

    public function moveDatabaseFiles()
    {
        /**
         * Paths
         */
        $databaseDirectory = TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'db_import';
        $newDatabaseDirectory = TRANSFERITO_ABSPATH . 'transferito_import';

        /**
         * Command
         */
        $moveCommand = "mv {$databaseDirectory} {$newDatabaseDirectory}";

        /**
         * Save the PID
         */
        $pid = exec("{$moveCommand} > /dev/null & echo $!;");

        /**
         * Save the PID
         */
        set_transient('transferito_database_relocation_pid', $pid);
    }

    public function prepareTableMap()
    {
        $driver = new mysqli_driver();
        $driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

        try {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $mysqli->select_db(DB_NAME);
            $queryTables = $mysqli->query('SHOW TABLES');
            $tableMap = [];

            /**
             * Loop through the tables that have been found
             */
            while($row = $queryTables->fetch_row()) {
                $foundTables[] = $row[0];
                $table = $row[0];

                /**
                 * Get the table status for the looping table
                 */
                $tableStatus = $mysqli
                    ->query("SHOW TABLE STATUS WHERE name='{$table}'")
                    ->fetch_assoc();

                /**
                 * Get the table row count for the looping table
                 */
                $tableRowCount = $mysqli
                    ->query("SELECT COUNT(*) FROM {$table}")
                    ->fetch_row();

                /**
                 * Map the table with the row amount and the row length
                 */
                $tableMap[] = [
                    'name'      => $table,
                    'rowAmount' => $tableRowCount[0],
                    'rowLength' => $tableStatus['Avg_row_length'],
                    'tableSize' => $tableStatus['Data_length']
                ];
            }

            return $tableMap;
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }

    public function saveDatabaseExportPart($fileIndex, $content)
    {
        $directory = TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'db_import';
        $filename = $directory . DIRECTORY_SEPARATOR . 'transferito_db_import_' . $fileIndex . '.sql';

        /**
         * Check that to the directory has been created
         * If it hasn't then create it
         */
        if (!file_exists($directory)) {
            mkdir($directory);
        }

        return file_put_contents($filename, $content);
    }

    public function chunkedDBExport($fileIndex = 1, $startingRowIndex = 0, $startingTableIndex = 0)
    {
        $driver = new mysqli_driver();
        $driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

        try {
            $cycleCount = 0;
            $byteCount = 0;
            $initialOffset = $startingRowIndex;
            $recordCount = 0;
            $initialOffsetBeenSet = false;
            $tableMap = get_transient('transferito_database_table_map');
            $transferDetail = get_transient('transferito_transfer_detail');

            $oldURL = $transferDetail['fromUrl'];
            $newURL = $transferDetail['newUrl'];

            $parsedOldURL = parse_url($oldURL);
            $parsedNewURL = parse_url($newURL);

            $escapedOldHost = '/' . $parsedOldURL['host'];
            $escapedNewHost = '/' . $parsedNewURL['host'];

            $escapedOldHostEncoded = '%2F' . $parsedOldURL['host'];
            $escapedNewHostEncoded = '%2F' . $parsedNewURL['host'];

            $tableMapAmount = count($tableMap);

            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $mysqli->select_db(DB_NAME);

            $databaseName = DB_NAME;
            $charsetQuery = $mysqli->query(
                "SELECT `DEFAULT_CHARACTER_SET_NAME` FROM information_schema.SCHEMATA WHERE schema_name ='{$databaseName}'"
            )->fetch_row();

            /**
             * Check to see if the charset is available
             */
            if (!isset($charsetQuery[0])) {
                throw new \Exception('We can not get your charset.');
            }

            $charset = $charsetQuery[0];

            /**
             * @todo Clean up and remove properly
             */
            set_transient('transferito_database_charset_info', [
                'actualCharset'     => $charset,
                'configCharset'     => DB_CHARSET,
            ]);

            $settings = get_option('transferito_settings_option');
            $useDefaultCollation = isset($settings['transferito_use_default_collation'])
                ? $settings['transferito_use_default_collation']
                : false;

            $sqlContent = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n/*!40101 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS */;\n/*!40101 SET SESSION sql_mode = 'ALLOW_INVALID_DATES' */;\n/*!40101 SET FOREIGN_KEY_CHECKS=0 */;\n/*!40101 SET NAMES utf8mb4 */;\n\n";

            $exportResult = [];

            for ($tableIndex = $startingTableIndex; $tableIndex < $tableMapAmount;) {
                /**
                 * If the row is at 0
                 * Then print the create table SQL with the drop table statement
                 */
                if ($startingRowIndex === 0) {
                    $createTable = $mysqli->query('SHOW CREATE TABLE ' . $tableMap[$tableIndex]['name'])->fetch_row();
                    $createTableSQL = $createTable[1];

                    if ($useDefaultCollation) {
                        $columnUpdatedSQL = $this->removeCollation($createTableSQL, 'column');
                        $tableUpdatedSQL = $this->removeCollation($columnUpdatedSQL, 'table');
                        $sqlContent .= "\n\nDROP TABLE IF EXISTS `{$tableMap[$tableIndex]['name']}`;\n" . $tableUpdatedSQL . ";\n\n";
                    } else {
                        $sqlContent .= "\n\nDROP TABLE IF EXISTS `{$tableMap[$tableIndex]['name']}`;\n" . $createTableSQL . ";\n\n";
                    }
                }

                /**
                 * Query to pull the row data from this table
                 */
                if ($startingRowIndex === 0 || $initialOffset === 0) {
                    $command = "SELECT * FROM {$tableMap[$tableIndex]['name']} LIMIT 10000";
                } else {
                    $command = "SELECT * FROM {$tableMap[$tableIndex]['name']} LIMIT 10000 OFFSET {$initialOffset}";
                }

                $rowData = $mysqli->query($command);

                /**
                 * Loop through the rows based on the starting index
                 */
                for($rowIndex = 0; $rowIndex < $rowData->num_rows;) {
                    $rowData->data_seek($rowIndex);
                    $row = $rowData->fetch_row();

                    /**
                     * Correct byte allocation
                     */
                    $byteCount = $byteCount + strlen(serialize($row));

                    /**
                     * Add the insert statement on cycle count or first row
                     */
                    if ($rowIndex == 0 || $cycleCount % 100 == 0 || $cycleCount == 0) {
                        $sqlContent .= "\nINSERT IGNORE INTO " . $tableMap[$tableIndex]['name']. " VALUES ";
                    }

                    /**
                     * Open the bracket for the row value
                     */
                    $sqlContent .= "\n(";

                    /**
                     * Loop through the columns for the row
                     */
                    for ($fieldIndex = 0; $fieldIndex < $rowData->field_count; $fieldIndex++) {
                        /**
                         * Replace the URLs
                         */
                        if (strpos($row[$fieldIndex], $oldURL) !== false) {
                            $row[$fieldIndex] = str_replace($oldURL, $newURL, $row[$fieldIndex]);
                        }

                        /**
                         * Replace the escaped domain
                         */
                        if (strpos($row[$fieldIndex], $escapedOldHost) !== false) {
                            $row[$fieldIndex] = str_replace($escapedOldHost, $escapedNewHost, $row[$fieldIndex]);
                        }

                        /**
                         * Replace the escaped url encoded domain
                         */
                        if (strpos($row[$fieldIndex], $escapedOldHostEncoded) !== false) {
                            $row[$fieldIndex] = str_replace($escapedOldHostEncoded, $escapedNewHostEncoded, $row[$fieldIndex]);
                        }

                        /**
                         * Fix broken serialized data
                         */
                        if (substr($row[$fieldIndex], 0, 2) === 'a:') {
                            if (!@unserialize($row[$fieldIndex])) {
                                $fixedSerialization = preg_replace_callback(
                                    '/s:([0-9]+):\"(.*?)\";/',
                                    function ($matches) { return "s:".strlen($matches[2]).':"'.$matches[2].'";';     },
                                    $row[$fieldIndex]
                                );
                                $row[$fieldIndex] = $fixedSerialization;
                            }
                        }

                        /**
                         * Add slashes to the field value
                         */
                        $row[$fieldIndex] = $mysqli->real_escape_string($row[$fieldIndex]);

                        /**
                         * Check the value exists for the column value
                         */
                        if (isset($row[$fieldIndex])) {
                            $sqlContent .= "'" . $row[$fieldIndex] . "'" ;
                        } else {
                            $sqlContent .= "''";
                        }

                        /**
                         * Adda a comma to every column for the value statement except the penultimate
                         */
                        if ($fieldIndex < ($rowData->field_count - 1)) {
                            $sqlContent.= ",";
                        }
                    }

                    /**
                     * Close the bracket for the row value
                     */
                    $sqlContent .=")";

                    /**
                     * Dependent on the cycle or the end of the row value
                     * Add a comma or end the statement
                     */
                    if ((($cycleCount + 1) %100 == 0 && $cycleCount != 0) || ($rowIndex + 1) == $rowData->num_rows) {
                        $sqlContent .= ";";
                    } else {
                        $sqlContent .= ",";
                    }
                    $cycleCount = $cycleCount + 1;

                    /**
                     * Increment the row index
                     */
                    $rowIndex++;

                    /**
                     * Records Count
                     */
                    $recordCount = $rowIndex;

                    /**
                     * Every 10000 break
                     */
                    if ($rowIndex != 0 && $rowIndex % 10000 == 0) {
                        /**
                         * Current row index
                         */
                        $initialOffset = $initialOffset + $recordCount;
                        $initialOffsetBeenSet = true;
                        $command = "SELECT * FROM {$tableMap[$tableIndex]['name']} LIMIT 10000 OFFSET {$initialOffset}";
                        $rowData = $mysqli->query($command);
                        $rowIndex = 0;
                    }

                    /**
                     * If the byte count is greater than or equal to LIMIT
                     */
                    if ($byteCount >= TRANSFERITO_DB_LIMIT) {

                        /**
                         * If initial offset has already been set
                         * Then do not assign it again
                         */
                        if (!$initialOffsetBeenSet) {
                            $initialOffset = $initialOffset + $recordCount;
                        }

                        /**
                         * Check the last character in the import
                         */
                        if (substr($sqlContent, -1) === ',') {
                            $sqlContent = substr($sqlContent, 0, -1);
                            $sqlContent .= ";";
                        }

                        /**
                         * Add to the end of every file
                         */
                        $sqlContent .= "\n\n/*!40101 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;";

                        /**
                         * Save the export file
                         */
                        $this->saveDatabaseExportPart($fileIndex, $sqlContent);

                        /**
                         * Then increment the file index once the file has been saved
                         */
                        $fileIndex++;

                        /**
                         * Set the transient DB Export Progress
                         */
                        set_transient('transferito_db_export_progress', [
                            'currentRowIndex'   => $initialOffset,
                            'tableIndex'        => $tableIndex,
                            'fileIndex'         => $fileIndex
                        ]);

                        /**
                         * Set the export flag to true
                         * Which will notify the API of completion
                         */
                        $exportResult = [
                            'completed' => false,
                            'currentRowIndex'   => $initialOffset,
                            'tableIndex'        => $tableIndex,
                            'fileIndex'         => $fileIndex,
                        ];

                        break;
                    }
                }

                /**
                 * If we've reached the byte count
                 * Go no further than this
                 */
                if ($byteCount >= TRANSFERITO_DB_LIMIT) {
                    break;
                }

                /**
                 * Increment the table index
                 */
                $tableIndex++;

                /**
                 * Reset the starting row index
                 */
                $startingRowIndex = 0;

                /**
                 * Reset the offset
                 */
                $initialOffset = 0;

                /**
                 * Assign a falsey value to the been set flag to reset it
                 */
                $initialOffsetBeenSet = false;

                /**
                 * When the DB export has finished
                 */
                if ($tableIndex === $tableMapAmount) {
                    /**
                     * Save the export file
                     */
                    $this->saveDatabaseExportPart($fileIndex, $sqlContent);

                    /**
                     * Set the export flag to true
                     * Which will notify the API of completion
                     */
                    $exportResult = [ 'completed' => true ];
                }
            }

            return $exportResult;
        } catch(mysqli_sql_exception $e) {
            return false;
        }
    }

    public function createFileList()
    {
        try {
            $directory = TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'db_import';
            $fileIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::CHILD_FIRST);
            $files = array();
            $byteCount = 0;
            $fileSuffix = 1;

            /**
             * Create the directory to house the json files
             */
            $dbExportDirectory = $directory . DIRECTORY_SEPARATOR . 'json';
            mkdir($dbExportDirectory);

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
                $byteCount = $byteCount + $file->getSize();

                /**
                 * Split the path to just get the filename
                 */
                $updatedName = explode(DIRECTORY_SEPARATOR . 'db_import' . DIRECTORY_SEPARATOR, $filename);

                /**
                 * Check that we're only checking SQL files
                 */
                if (strpos($updatedName[1], '.sql') !== false) {
                    /**
                     * If the byte count is greater than the archive limit - split the file
                     */
                    if ($byteCount >= TRANSFERITO_DB_LIMIT) {
                        file_put_contents($dbExportDirectory . DIRECTORY_SEPARATOR . 'file_list_' . $fileSuffix . '.json', json_encode($files));

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
                        $files[] = [
                            'originalPath'  => $filename,
                            'updatedPath'   => str_replace("\\", "/", 'transferito_import' . DIRECTORY_SEPARATOR . $updatedName[1]),
                        ];
                    }

                    /**
                     * Create path based array
                     */
                    if (!$isZipArchive) {
                        $files['transferito_import' . DIRECTORY_SEPARATOR . $updatedName[1]] = $filename;
                    }
                }
            }

            /**
             * Create the last file
             */
            if (count($files) > 0) {
                file_put_contents($dbExportDirectory . DIRECTORY_SEPARATOR . 'file_list_' . $fileSuffix . '.json', json_encode($files));
            }

            return [
                'created'   => true,
                'amount'    => $fileSuffix
            ];

        } catch (\Exception $exception) {
            return false;
        }
    }
}
