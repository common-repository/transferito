<?php

namespace Transferito\Controllers;

use Transferito\Models\Core\Config;
use Transferito\Models\Transfer\CodeBase;
use Transferito\Models\Transfer\Database;
use Transferito\Models\Transfer\Upload;
use Transferito\Models\Core\Api as TransferitoAPI;
use Transferito\Models\Settings\Telemetry;

class Transfer {

    private $codeBase;
    private $dataBase;
    private $upload;
    private $api;
    private $options;
    private $telemetry;

    public function __construct()
    {
        if (current_user_can('activate_plugins')) {
	        $this->api = new TransferitoAPI();
	        $this->codeBase = new CodeBase();
            $this->dataBase = new Database();
            $this->upload = new Upload();
            $this->telemetry = new Telemetry();

	        $this->options = get_option( 'transferito_settings_option' );

            add_action("wp_ajax_preparing_transfer", [ $this, "prepareDownload"]);
            add_action("wp_ajax_start_migration", [ $this, "startMigration"]);
            add_action("wp_ajax_clean_up_files", [ $this, "cleanUp"]);
            add_action("wp_ajax_status_check", [ $this, "statusCheck"]);

            /**
             * @deprecated
             */
            add_action("wp_ajax_get_directories", [ $this, "getFTPDirectories"]);

            add_action("wp_ajax_initiate_local_upload", [ $this, "initiateUpload"]);
            add_action("wp_ajax_upload_chunk", [ $this, "uploadChunk"]);
            add_action("wp_ajax_complete_upload", [ $this, "completeUpload"]);
            add_action("wp_ajax_preparing_codebase", [ $this, "prepareCodebase"]);
            add_action("wp_ajax_add_files_to_codebase_archive", [ $this, "addFilesToCodebase"]);
            add_action("wp_ajax_codebase_completion", [ $this, "codebaseArchiveComplete"]);
	        add_action("wp_ajax_preparing_database", [ $this, "prepareDatabase"]);
	        add_action("wp_ajax_create_db_exports", [ $this, "chunkedDBExport"]);
	        add_action("wp_ajax_database_completion", [ $this, "databaseExportComplete"]);
	        add_action("wp_ajax_archive_db_exports", [ $this, "archiveDBExport"]);
	        add_action("wp_ajax_database_relocation", [ $this, "databaseRelocation"]);
	        add_action("wp_ajax_database_relocation_check", [ $this, "databaseRelocationCheck"]);
	        add_action("wp_ajax_check_archive_completion", [ $this, "checkArchiveCompletion"]);
	        add_action("wp_ajax_archive_creation", [ $this, "archiveCreation"]);
	        add_action("wp_ajax_archive_progress_check", [ $this, "archiveProgressCheck"]);
	        add_action("wp_ajax_cpanel_authentication", [ $this, "cpanelAuthentication"]);
	        add_action("wp_ajax_server_detail_validation", [ $this, "serverDetailValidation"]);

            /**
             * @deprecated
             */
	        add_action("wp_ajax_correct_directory_validation", [ $this, "directoryValidation"]);

	        add_action("wp_ajax_database_detail_validation", [ $this, "databaseValidation"]);
	        add_action("wp_ajax_hide_quickstart_popup", [ $this, "hideQuickStart"]);
	        add_action("wp_ajax_send_request_form", [ $this, "sendRequestForm"]);
	        add_action("wp_ajax_log_transferito_event", [ $this, "logEvent"]);
            add_action("wp_ajax_start_directory_search", [ $this, "startDirectoryCheck"]);
            add_action("wp_ajax_get_directory_check_update", [ $this, "getDirectoryCheckUpdate"]);


            /**
	         * Move to routing class
	         */
            add_action("wp_ajax_check_current_site", [$this, "wpSiteCheck"]);
            add_action("wp_ajax_check_cpanel_availability", [$this, "cPanelCheck"]);
            add_action("wp_ajax_choose_migration_method", [$this, "chooseMigrationMethod"]);
            add_action("wp_ajax_switch_mode", [$this, "switchMode"]);
            add_action("wp_ajax_screen_route_redirection", [$this, "screenRouting"]);
            add_action("wp_ajax_load_directory_template", [$this, "loadDirectoryTemplate"]);
        }
    }

    public function logEvent()
    {
        $event = $_POST['event'];
        $eventProperties = $_POST['eventProperties'];

        $this->telemetry->pushEvent($event, $eventProperties);

        wp_send_json_success([ 'logged' => true ]);
    }

    /**
     * @deprecated
     * @return void
     */
    public function getFTPDirectories()
    {
	    /**
	     * verify nonce with every FTP Directory request
	     */
	    check_ajax_referer('get_directory_list', 'securityKey');

	    /**
	     * Check that the ftpInfo element exists
	     */
	    $getDirectoriesPayload = get_transient('transferito_manual_server_detail');

	    /**
	     * Assign the correct path to the ftp details
	     */
	    if (count($getDirectoriesPayload) > 0) {
		    $getDirectoriesPayload['path'] = $_POST['path'];
	    }


	    try {
		    /**
		     * Hit the endpoint to return the results
		     */
		    $getDirectoriesRequest = $this->api->getDirectories($getDirectoriesPayload);

		    /**
		     * Listen to a successful response
		     * & then filter the array to just return the directories
		     */
		    if ($getDirectoriesRequest['code'] === 200) {
			    $directoryList = $getDirectoriesRequest['message']->filelist;
			    $filteredDirectoryList = array_filter($directoryList, function($value, $key) {
				    return $value->type === 'dir';
			    }, ARRAY_FILTER_USE_BOTH);
			    wp_send_json_success([
			    	'folders'   => $filteredDirectoryList,
				    'port'      => $getDirectoriesPayload['ftpPort']
			    ]);
		    } else if ($getDirectoriesRequest['code'] !== 200) {
			    wp_send_json_error([
                    'err'       => $getDirectoriesRequest['message']->result,
                    'payload'   => $getDirectoriesPayload,
                    'message'   => $getDirectoriesRequest
                ], 400);
		    }
	    } catch(\Exception $exception) {
		    wp_send_json_error('There has been an issue - If this problem persists. Contact support', 500);
	    }
    }

    public function startDirectoryCheck()
    {
        /**
         * verify nonce with every FTP Directory request
         */
        check_ajax_referer('get_directory_list', 'securityKey');

        /**
         * Check that the ftpInfo element exists
         */
        $directoryCheckPayload = get_transient('transferito_manual_server_detail');

        /**
         * Get the destination server URL
         */
        $domain = get_transient('transferito_migration_domain');

        /**
         * Make initial Directory Check request
         */
        $directoryCheck = $this->api->startDirectoryCheck(array(
            'ftpHost'   => $directoryCheckPayload['ftpHost'],
            'ftpUser'   => $directoryCheckPayload['ftpUser'],
            'ftpPass'   => $directoryCheckPayload['ftpPass'],
            'ftpPort'   => $directoryCheckPayload['ftpPort'],
            'useSFTP'   => $directoryCheckPayload['useSFTP'],
            'URL'       => $domain,
        ));

        /**
         * Check the status code
         */
        $httpStatusCode = $directoryCheck['code'];

        /**
         * Check we have a successful response
         */
        if ($httpStatusCode === 200) {
            wp_send_json_success(array_merge((array)$directoryCheck['message'], $directoryCheckPayload));
        }

        /**
         * If we have anything other than a 200
         * Fail the endpoint
         */
        if ($httpStatusCode !== 200) {
            wp_send_json_error($directoryCheck['message'], 400);
        }

    }

    public function getDirectoryCheckUpdate()
    {
        /**
         * verify nonce with every FTP Directory request
         */
        check_ajax_referer('get_directory_list', 'securityKey');

        $url = isset($_POST['url']) ? $_POST['url'] : '';
        $directoryCheckId =  isset($_POST['directoryCheckId']) ? $_POST['directoryCheckId'] : '';
        $directoryCheckUpdate = $this->api->getDirectoryUpdate(array(
            'URL'               => $url,
            'directoryCheckId'  => $directoryCheckId
        ));

        /**
         * Check the status code
         */
        $httpStatusCode = $directoryCheckUpdate['code'];

        /**
         * If we have anything other than a 200
         * Fail the endpoint
         */
        if ($httpStatusCode !== 200) {
            wp_send_json_error($directoryCheckUpdate['message'], 400);
        }

        /**
         * If the path has been found
         * Update the Server Detail
         */
        if ($directoryCheckUpdate['message']->found) {
            $serverDetails = get_transient('transferito_manual_server_detail');

            /**
             * Fix to add the path if the document root is the ftp path
             */
            if ($directoryCheckUpdate['message']->path === '' && $serverDetails['useSFTP'] === '0') {
                $directoryCheckUpdate['message']->path = './';
            }

            /**
             * Add the path to the server details array
             */
            $serverDetails['path'] = $directoryCheckUpdate['message']->path;
            $serverDetails['ftpPath'] = $directoryCheckUpdate['message']->path;
            $serverDetails['URL'] = $url;

            /**
             * Update the server array
             */
            set_transient('transferito_manual_server_detail', $serverDetails);
        }

        wp_send_json_success($directoryCheckUpdate['message']);
    }

	public function wpSiteCheck()
	{
		/**
		 * verify nonce with every template change call
		 */
		check_ajax_referer('template_change', 'actionKey');

		/**
		 * Clean up previous migration that may have failed
		 */
		$this->freshStart();

		getDirectorySize(TRANSFERITO_ABSPATH);
		$zipEnabled = class_exists('ZipArchive');
        $getWPInstallationSizes = get_transient('transferito_installation_size');

        /**
         * Log any failures created during the getDirectorySize check
         */
        if (isset($getWPInstallationSizes['errors']) && count($getWPInstallationSizes['errors']) > 0) {
            $errorMessage = implode('|', $getWPInstallationSizes['errors']);
            $this->api->failedMigration($errorMessage);
        }

        /**
         * Default the exec enabled flag to false
         */
        $execEnabled = false;

        /**
         * Get the ByPass exec usage Flag from the settings
         */
        $settingsOption = get_option('transferito_settings_option');
        $bypassExecUsage = isset($settingsOption['transferito_bypass_exec_archive_creation'])
            ? $settingsOption['transferito_bypass_exec_archive_creation']
            : false;

        /**
         * If the flag is not set
         * Check to see if the OS is windows
         * If it is not - Check to see if exec can be used
         */
        if (!$bypassExecUsage) {
            /**
             * Windows HOT Fix
             * @todo return to the original check when FULL FIX has been implemented
             */
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            $execEnabled = $isWindows
                ? false
                : function_exists('exec') && @exec('echo EXEC') == 'EXEC';
        }


		$metRequirements = !$zipEnabled && !$execEnabled ? false : true;
		$useZip = !$execEnabled;

		/**
		 * Save the requirements on load
		 */
		set_transient('transferito_requirements', [
			'metRequirements'   => $metRequirements,
			'useZip'            => $useZip
		]);

		/**
		 * Initially default the fallback to false
		 */
		set_transient('transferito_request_fallback', false);

		/**
		 * Check the user's plan information  - If API keys are present
		 */
		if ($this->options && $this->options['public_transferito_key'] && $this->options['secret_transferito_key']) {
			$planInfo = $this->api->planInformation();
			$code = $planInfo['code'];
			$availableTransfers = isset($planInfo['message']->availableTransfers)
				? $planInfo['message']->availableTransfers
				: 0;

            set_transient('transferito_has_available_transfers', ($availableTransfers !== 0));

			/**
			 * Check that the user's api keys are correct & they have at least 1 available transfer left
			 */
			if ($code === 200 && $availableTransfers !== 0) {
				/**
				 * Pass in a flag to change the message
				 */
                set_transient('transferito_user_status', 'PREMIUM');
                $response = [
					'htmlTemplate'  => loadTemplate('parts/migration/cpanel-check', [
						'secondaryMessage'  => 'To start your migration, please choose a migration method',
						'metRequirements'   => $metRequirements,
                        'hideQuickStart'    => true
					])
				];
			} else {
                set_transient('transferito_user_status', 'FREE');

                $response = $this->selectCorrectTemplate([
                    'info'              => $planInfo,
                    'code'              => $code,
                    'transfers'         => $availableTransfers,
                    'hideQuickStart'    => true,
					'noTransfersLeft'   => true
				]);
			}
		} else {
            set_transient('transferito_user_status', 'FREE');
            $response = $this->selectCorrectTemplate();
		}

        /**
         * Push loadScreen event to telemetry
         */
        $this->telemetry->pushEvent('loadScreen', [
            'screenName'    => 'DestinationURL'
        ]);

		wp_send_json_success($response);
	}

	public function switchMode()
	{
		$transferMethod = $_POST['method'];
		$domain = get_transient('transferito_migration_domain');
		$cPanelAllowed = boolval(get_transient('transferito_cpanel_allowed'));

        /**
         * Has the cpanel domain
         */
        $hasCpanelDomain = get_transient('transferito_cpanel_domain');

        /**
         * Set / Remove the transient based on the transfer method
         */
        if ($transferMethod === 'cpanel' && !$hasCpanelDomain) {
            /**
             * Domain
             */
            $updatedDomain = rtrim(trim($domain), '/');

            /**
             * cPanel admin URL
             */
            $cpanelAdminURL = $updatedDomain . ':2083';

            /**
             * Result from the cPanel check
             */
            $response = $this->api->cPanelAvailabilityCheck($cpanelAdminURL);

            /**
             * Check that the string is in the response
             */
            $cPanelInHTMLSource = stripos($response['message'], 'cpanel') !== false;

            /**
             * Check if we should default to cPanel
             */
            $cPanelAllowed = $response['code'] === 200 && $cPanelInHTMLSource;

            /**
             * Correct cPanel URL
             */
            $cpanelFinalAdminURLSplit = explode(':2083', $response['url']);

            if ($cPanelAllowed) {
                set_transient('transferito_cpanel_domain', $cpanelFinalAdminURLSplit[0]);
            }
        }

        /**
         * Update transient - For the transfer method
         */
        $updatedTransferMethod = $transferMethod === 'cpanel' ? 'cpanel' : 'manual';
        set_transient('transferito_transfer_method', $updatedTransferMethod);

        /**
         * Build the part dynamically
         */
        $templatePath = 'parts/migration/' . $transferMethod . '/main';

        /**
         * Load the correct template based on the site size
         */
        $htmlTemplate = loadTemplate($templatePath, [
            'cpanelAllowed'     => $cPanelAllowed,
            'directories'       => Config::getWPContentPaths(),
            'cpanelDetail'      => get_transient('transferito_cpanel_auth_details'),
            'cpanelCompleted'   => get_transient('transferito_cpanel_auth_details_completed'),
            'detail'            => get_transient('transferito_manual_server_detail')
        ]);

        /**
         * Push loadScreen event to telemetry
         */
        $this->telemetry->pushEvent('loadScreen', [
            'screenName'    => $transferMethod === 'cpanel' ? 'cPanelAuthentication' : 'FTPAuthentication'
        ]);

        /**
		 * Return the object
		 */
		wp_send_json_success([
			'cPanelAllowed'     => $cPanelAllowed,
			'URL'               => $domain,
			'transferMethod'    => $transferMethod,
			'htmlTemplate'      => $htmlTemplate,
		]);
	}

    public function screenRouting()
    {
        try {
            $screenRoute = $_POST['route'];
            $serverDetail = get_transient('transferito_manual_server_detail');
            $url = explode('://', get_transient('transferito_migration_domain'));
            $domain = count($url) === 2 ? $url[1] : $url[0];
            $cPanelDomainList = get_transient('transferito_cpanel_domain_selection');

            $mappedScreenRoutes = [
                'destinationURL'            => loadTemplate('parts/migration/cpanel-check', [
                    'url'               => $domain,
                    'mainMessage'       => '',
                    'secondaryMessage'  => 'To start your migration, please choose a migration method',
                    'metRequirements'   => get_transient('transferito_requirements')['metRequirements'],
                    'hideQuickStart'    => true
                ]),
                'migrationMethodSelection'  => loadTemplate('parts/migration/select-migration-method', [
                    'cpanelAllowed'     => get_transient('transferito_cpanel_allowed'),
                    'transferMethod'    => get_transient('transferito_transfer_method')
                ]),
                'cpanelAuthentication'      => loadTemplate('parts/migration/cpanel/main', [
                    'cpanelDetail'      => get_transient('transferito_cpanel_auth_details'),
                    'cpanelCompleted'   => get_transient('transferito_cpanel_auth_details_completed')
                ]),
                'cpanelDomainSelection'      => loadTemplate('parts/migration/cpanel/domain-selection', $cPanelDomainList ? $cPanelDomainList : []),
                'ftpAuthentication'         => loadTemplate('parts/migration/manual/main', [
                    'directories'       => Config::getWPContentPaths(),
                    'detail'            => $serverDetail
                ]),
                'directorySelector'         => loadTemplate('parts/migration/manual/directory-selection', []),
                'databaseAuthentication'    => loadTemplate('parts/migration/manual/database-detail', [
                    'detail'            => $serverDetail,
                    'completed'         => get_transient('transferito_database_detail_completed')
                ])
            ];

            /**
             * If the route does not exist in the mapped routes
             *
             * @todo throw error
             */
            if (!isset($mappedScreenRoutes[$screenRoute])) {
                wp_send_json_error([
                    'message' => 'routeDoesNotExist'
                ], 400);
            }

            /**
             * Return the object
             */
            wp_send_json_success([
                'htmlTemplate'  => $mappedScreenRoutes[$screenRoute],
                'detail'        => $serverDetail
            ]);
        } catch (\Exception $exception) {
            wp_send_json_error([
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    public function sendRequestForm()
    {
        check_ajax_referer('hosting_guide_request_detail', 'securityKey');

        $this->api->hostingGuideRequest([
            'email'             => $_POST['data']['emailAddress'],
            'hostingProvider'   => $_POST['data']['hostingProvider'],
            'guideName'         => $_POST['data']['guideName']
        ]);

        /**
         * Return the object
         */
        wp_send_json_success([ 'completed' => true ]);
    }

    /**
     * @remove all functionality for quickstart
     */
    public function hideQuickStart()
    {
        set_transient('transferito_hide_quick_start_guide', true);

        /**
         * Return the object
         */
        wp_send_json_success(['completed' => true ]);
    }

	public function loadDirectoryTemplate()
	{
		$serverDetail = get_transient('transferito_manual_server_detail');

        /**
         * Push loadScreen event to telemetry
         */
        $this->telemetry->pushEvent('loadScreen', [
            'screenName'    => 'directorySelector'
        ]);

		/**
		 * Return success object
		 * To update the nav and change the child template
		 */
		wp_send_json_success([
			'template'          => loadTemplate('parts/migration/manual/directory-selection', []),
			'path'              => $serverDetail['path'],
			'navigation'        => [
				'completed'     => 'transferitoNav__manualFTPDetails',
				'active'        => 'transferitoNav__manualFTPDirectorySelect'
			],
		]);

	}

	public function cPanelCheck()
	{
		/**
		 * verify nonce with every template change call
		 */
		check_ajax_referer('cpanel_check', 'securityKey');

        $splitURL = explode('://', $_POST['domain']);

        /**
         * Check user hasn't used double protocol
         */
        if (count($splitURL) !== 2) {
            wp_send_json_error([
                'message' => 'Failed URL Check'
            ], 400);
        }

		/**
		 * Domain
		 */
		$domain = rtrim(trim($_POST['domain']), '/');

		/**
		 * cPanel admin URL
		 */
		$cpanelAdminURL = $domain . ':2083';

		/**
		 * Result from the cPanel check
		 */
		$response = $this->api->cPanelAvailabilityCheck($cpanelAdminURL);

		/**
		 * Check that the string is in the response
		 */
		$cPanelInHTMLSource = stripos($response['message'], 'cpanel') !== false;

		/**
		 * Check if we should default to cPanel
		 */
		$cPanelAllowed = $response['code'] === 200 && $cPanelInHTMLSource;

		/**
		 * Default transfer method
		 */
		$transferMethod = $cPanelAllowed ? 'cpanel' : 'manual';

		/**
		 * Correct cPanel URL
		 */
		$cpanelFinalAdminURLSplit = explode(':2083', $response['url']);

		/**
		 * Save the domain
		 */
		set_transient('transferito_migration_domain', $domain);
		set_transient('transferito_migration_unchanged_domain', $domain);
		set_transient('transferito_cpanel_allowed', $cPanelAllowed);
		set_transient('transferito_transfer_method', $transferMethod);

		/**
		 * Only save if cPanel is allowed
		 */
		if ($cPanelAllowed) {
			set_transient('transferito_cpanel_domain', $cpanelFinalAdminURLSplit[0]);
		} else {
            set_transient('transferito_cpanel_domain', $domain);
        }

		/**
		 * Load the correct template based on whether cpanel is allowed or not
		 */
		$htmlTemplate = loadTemplate('parts/migration/select-migration-method', [
            'cpanelAllowed'     => $cPanelAllowed,
            'transferMethod'    => $transferMethod,
        ]);

        /**
         * Push loadScreen event to telemetry
         */
        $this->telemetry->pushEvent('loadScreen', [
            'screenName'    => 'selectMigrationMethod'
        ]);

		wp_send_json_success([
			't'                 => $cpanelFinalAdminURLSplit[0],
			'cPanelAllowed'     => $cPanelAllowed,
			'URL'               => $domain,
			'transferMethod'    => $transferMethod,
			'htmlTemplate'      => $htmlTemplate,
		]);
	}

	public function cpanelAuthentication()
	{
		try {
			check_ajax_referer('cpanel_migration', 'securityKey');

			/**
			 * Get the domain
			 */
			$domain = get_transient('transferito_cpanel_domain');

            /**
             *  Get the destination URL
             */
            $destinationURL = get_transient('transferito_migration_domain');

            /**
             * Split the destination URL into a domain via the protocol
             */
            $splitDestinationURL = explode('//', $destinationURL);

            /**
             * Get the destination Domain
             */
            $destinationDomain = count($splitDestinationURL) === 2 ? $splitDestinationURL[1] : $destinationURL;

			/**
			 * Pull the auth details
			 */
			$cPanelDetails = $_POST['auth'];
			$cPanelDetails['cpanelPass'] = $cPanelDetails['cpanelPass'];
			$cPanelDetails['cPanelUseApiToken'] = isset($cPanelDetails['cPanelUseApiToken']) ? false : true;

			/**
			 * Updated the cPanel array
			 */
			$updatedAuthArray = array_merge($cPanelDetails, [ 'cpanelHost' => $domain ]);

			/**
			 * Make a request to the endpoint to get a list of the available domains
			 */
			$authResult = $this->api->cPanelAuth($updatedAuthArray);

			/**
			 * If there is an error authenticating
			 */
			if ($authResult['code'] !== 200) {
				throw new \Exception(stripslashes($authResult['message']));
			}

			/**
			 * Get the list of selectable domains
			 */
			$availableDomains = $authResult['message'];

            /**
             * Save the cPanel details
             */
            set_transient('transferito_cpanel_auth_details', $cPanelDetails);

            /**
             * Update the completed flag
             */
            set_transient('transferito_cpanel_auth_details_completed', true);

            /**
             * Push loadScreen event to telemetry
             */
            $this->telemetry->pushEvent('loadScreen', [
                'screenName'    => 'cPanelDomainSelection'
            ]);

            /**
             * Assign the data needed for the template to a variable to save
             */
            $domainSelectionOptions = [
                'domain'        => $destinationDomain,
                'domains'       => $availableDomains,
                'username'      => $cPanelDetails['cpanelUser'],
                'password'      => $cPanelDetails['cpanelPass'],
                'apiToken'      => $cPanelDetails['cPanelApiToken'],
                'useApiToken'   => $cPanelDetails['cPanelUseApiToken'],
                'URL'           => $domain,
            ];

            /**
             * Save the domain selection data needed for the template
             */
            set_transient('transferito_cpanel_domain_selection', $domainSelectionOptions);

            /**
			 * Return success object
			 * To update the nav and change the child template
			 */
			wp_send_json_success([
				'template'      => loadTemplate('parts/migration/cpanel/domain-selection', $domainSelectionOptions)
			]);

		} catch(\Exception $exception) {
			wp_send_json_error([
                'template'      => loadTemplate('parts/migration/cpanel/main', [
                    'showErrorPopup' => true
                ]),
			], 400);
		}
	}

	public function serverDetailValidation()
	{
		try {
			check_ajax_referer('manual_migration_server_detail', 'securityKey');

			/**
			 * Pull the server details
			 */
			$serverDetails = $_POST['serverDetails'];

			/**
			 * Updated the server details array
			 */
			$updatedServerDetailArray = array_merge($serverDetails, [ 'ftpPath' => '.' ]);

			/**
			 * Set the server detail as a transient
			 */
			set_transient('transferito_manual_server_detail', $updatedServerDetailArray);

			/**
			 * Make a request to the endpoint to get a list of the available domains
			 */
			$serverDetailResult = $this->api->ftpValidation($updatedServerDetailArray);

			/**
			 * Fail when the FTP connection fails
			 */
			if ($serverDetailResult['code'] !== 200) {
				throw new \Exception('API_REQUEST_FAILURE');
			}

			/**
			 * Fail when the FTP connection fails
			 */
			if (!$serverDetailResult['message']) {
				throw new \Exception('FALSEY_RESPONSE');
			}

			/**
			 * Fail when the has connected property isn't present
			 */
			if (!property_exists($serverDetailResult['message'], 'hasConnected')) {
				throw new \Exception('PROPERTY_MISSING');
			}

			/**
			 * Fail when the FTP connection fails
			 */
			if (!$serverDetailResult['message']->hasConnected) {
				throw new \Exception('FTP_CONNECTION_FAILURE');
			}

            /**
             * Push loadScreen event to telemetry
             */
            $this->telemetry->pushEvent('loadScreen', [
                'screenName'    => 'directorySelector'
            ]);

            /**
             * Set the server detail as a transient
             */
            $updatedServerDetailArray['ftpPort'] = (string) $serverDetailResult['message']->ftpPort;
            $updatedServerDetailArray['useSFTP'] = (string) $serverDetailResult['message']->useSFTP;
            set_transient('transferito_manual_server_detail', $updatedServerDetailArray);

			/**
			 * Return success object
			 * To update the nav and change the child template
			 */
			wp_send_json_success([
				'template'  => loadTemplate('parts/migration/manual/directory-selection', []),
                'useSFTP'   => $updatedServerDetailArray['useSFTP']
			]);

		} catch (\Exception $exception) {
			wp_send_json_error([ 'connected' => false ], 400);
		}
	}

	public function directoryValidation()
	{
		try {
			check_ajax_referer('manual_migration_directory_selection', 'securityKey');

			/**
			 * Check that the ftpInfo element exists
			 */
			$serverDetail = get_transient('transferito_manual_server_detail');

			/**
			 * Get the saved domain to use as the domain to check
			 */
			$domain = get_transient('transferito_migration_domain');

			/**
			 * Create the updated server detail
			 */
			$directoryCheckPayload = array_merge($serverDetail, [
				'path'  => $_POST['directory'],
				'URL'   => $domain
			]);

			/**
			 * update the ftpPath from the payload
			 */
			$directoryCheckPayload['ftpPath'] = $_POST['directory'];

			/**
			 * Save the updated server detail
			 */
			set_transient('transferito_manual_server_detail', $directoryCheckPayload);

			/**
			 * Response from the directory check
			 */
			$response = $this->api->directoryCheck($directoryCheckPayload);

			/**
			 *
			 */
//			if ($response['code'] !== 200) {
//				throw new \Exception('FAILED_CORRECT_DIRECTORY_CHECK');
//			}

            /**
             * Push loadScreen event to telemetry
             */
//            $this->telemetry->pushEvent('loadScreen', [
//                'screenName'    => 'databaseAuthentication'
//            ]);

			/**
			 * Return success object
			 *
             * @todo Update the message -> check to see if the return message has the property correctDirectory
			 */
			wp_send_json_success($response['message']);

		} catch (\Exception $exception) {
			wp_send_json_error([
                'error'     => $exception->getMessage(),
				'template'  => loadTemplate('parts/migration/manual/directory-selection', []),
			], 400);
		}
	}

	public function databaseValidation()
	{
		try {
			check_ajax_referer('manual_migration_database_detail', 'securityKey');

			/**
			 * Check that the ftpInfo element exists
			 */
			$serverDetail = get_transient('transferito_manual_server_detail');

			/**
			 * Merge the array
			 */
			$databaseTestPayload = array_merge($serverDetail, $_POST['databaseDetail']);

			/**
			 * Exclude database
			 */
			$excludeDatabase = isset($_POST['databaseDetail']['exclude_database_transfer']) && $_POST['databaseDetail']['exclude_database_transfer'] === 'true';

			/**
			 * Assign the domain flag to the new payload
			 */
			$databaseTestPayload['domain'] = $databaseTestPayload['URL'];

			/**
			 * Update the server detail transient
			 */
			set_transient('transferito_manual_server_detail', $databaseTestPayload);

            /**
             * Add check that db detail has been completed
             */
            set_transient('transferito_database_detail_completed', true);

            /**
			 * Only do the DB check if the database isn't excluded
			 */
			if (!$excludeDatabase) {
				/**
				 * Make a request to the database validation endpoint
				 */
				$response = $this->api->databaseValidation($databaseTestPayload);

				/**
				 * Check to see whether the DB details have failed
				 */
				if ($response['code'] !== 200) {
					throw new \Exception('FAILED_DATABASE_CHECK');
				}
			}

			/**
			 * Final additions to the payload
			 */
			$migrationDetail = array_merge($databaseTestPayload, [
				'transferMethod'    => 'ftp',
				'transferType'      => 'manual'
			]);

			/**
			 * Pass the full details and nonce to validate the call
			 */
			wp_send_json_success([
				'migrationDetail'   => $migrationDetail,
				'securityKey'       => wp_create_nonce("prepare_migration_files")
			]);

		} catch (\Exception $exception) {
			/**
			 * Get the server detail
			 */
			$serverDetail = get_transient('transferito_manual_server_detail');

			/**
			 *
			 */
			wp_send_json_error([
				'template'      => loadTemplate('parts/migration/manual/database-detail', [
                    'detail'            => $serverDetail,
                    'completed'         => get_transient('transferito_database_detail_completed')
                ]),
			], 400);
		}
	}

	public function chooseMigrationMethod()
	{
		/**
		 * verify nonce with every template change call
		 */
		check_ajax_referer('migration_choice', 'actionKey');

		$freeTierUser = (!$this->options['public_transferito_key'] || !$this->options['secret_transferito_key']);
		$mainMessage = stripslashes('We are creating a backup of your current WordPress installation');
		$secondaryMessage = stripslashes('Please wait.. This may take a few minutes, do not close this window or refresh the page');

		$htmlTemplate = ($_POST['data']['migrationType'] === 'useCpanel')
			? loadTemplate('parts/migration/cpanel-validation', [
				'mainMessage'       => $mainMessage,
				'secondaryMessage'  => $secondaryMessage
			])
			: loadTemplate('parts/migration/transfer-detail-entry', [
				'mainMessage'       => $mainMessage,
				'secondaryMessage'  => $secondaryMessage,
				'freeMigration'     => $freeTierUser,
				'directories'       => Config::getWPContentPaths()
			]);

		$siteCheckResponse = [ 'htmlTemplate'  => $htmlTemplate ];

		wp_send_json_success($siteCheckResponse);
	}

	public function statusCheck()
	{
		$response = $this->api->getStatus(sanitize_text_field($_POST['token']));
		$responseMessage = $response['message'];

		/**
		 * Check the migration
		 */
		if ($response["code"] === 200) {
			$startCleanUp = get_transient('transferito_cleanup_after_completion');
			$isCompleted = (isset($responseMessage->status))
                ? $responseMessage->status === 'completed' || $responseMessage->status === 'completed.with.errors'
                : false;

			if ($responseMessage->status === 'completed' && $startCleanUp) {
				delete_transient('transferito_cleanup_after_completion');
			}

			wp_send_json_success([
				'metadata'  => $responseMessage->metaData,
				'statuses'  => $responseMessage->all,
				'status'    => $responseMessage->status,
				'completed' => $isCompleted
			]);

		} else {
			wp_send_json_error($responseMessage->error, $response['code']);
		}
    }

    public function prepareDownload()
    {
    	try {
		    check_ajax_referer('prepare_migration_files', 'security');

            /**
             * @DoNotRemove
		     * Important as it creates the access file to check whether the migration will be an upload
		     */
		    Config::getCorrectPath();

            $settings = get_option('transferito_settings_option');
            $forceUpload = isset($settings['transferito_force_upload']) ? $settings['transferito_force_upload'] : false;
            $migrationDetails = $_POST['migrationDetails'];
		    $excludeDatabase = isset($migrationDetails['exclude_database_transfer'])
			    ? $migrationDetails['exclude_database_transfer']
			    : null;
		    $folderPaths = isset($migrationDetails['folder_path'])
			    ? array_map('sanitize_text_field', wp_unslash($migrationDetails['folder_path']))
			    : null;
		    $destinationURL = isset($migrationDetails['domain'])
			    ? sanitize_text_field($migrationDetails['domain'])
			    : null;
		    $selectedFolderEnabled = ($folderPaths !== null);
		    $backupDirectory = bin2hex(openssl_random_pseudo_bytes(8));

            /**
             * Add the check to see if the options are selected
             */
            $disableWPObjectCache = isset($settings['transferito_disable_wordpress_cache'])
                ? $settings['transferito_disable_wordpress_cache']
                : false;
            $ignoreMalcareWAF = isset($settings['transferito_malcare_waf_plugin_fix'])
                ? $settings['transferito_malcare_waf_plugin_fix']
                : false;

            /**
             * Find the functions file - To Disable the WP Object Cache
             */
            if ($disableWPObjectCache) {
                $this->disableWPObjectCache();
            }

            /**
             * Find the user.ini file - To Disable MalCare WAF
             */
            if ($ignoreMalcareWAF) {
                $this->disableAutoPrependOption();
            }


            /**
		     * Set the destination URL transient
		     */
		    set_transient('transferito_migration_domain', $destinationURL);

            /**
             * Check if we can download directly from the server, unless the force upload flag is checked
             */
            if ($forceUpload) {
                $siteAccessed = false;
            } else {
                $sampleArchiveFilename = bin2hex(random_bytes(16)) . '.zip';

                /**
                 * Copy file
                 */
                if (copy(TRANSFERITO_PATH . 'sample.zip', TRANSFERITO_ABSPATH . $sampleArchiveFilename)) {
                    /**
                     * Request to check the direct download
                     */
                    $directDownload = $this->api->directDownloadCheck([
                        "transferMethod"    => $migrationDetails['transferMethod'],
                        'ftpHost'           => isset($migrationDetails['ftpHost']) ? $migrationDetails['ftpHost'] : null,
                        'ftpUser'           => isset($migrationDetails['ftpUser']) ? $migrationDetails['ftpUser'] : null,
                        'ftpPass'           => isset($migrationDetails['ftpPass']) ? $migrationDetails['ftpPass'] : null,
                        'ftpPort'           => isset($migrationDetails['ftpPort']) ? $migrationDetails['ftpPort'] : null,
                        'path'              => isset($migrationDetails['ftpPath']) ? $migrationDetails['ftpPath'] : null,
                        'useSFTP'           => isset($migrationDetails['useSFTP']) ? $migrationDetails['useSFTP'] : null,
                        "cpanelHost"        => isset($migrationDetails['cpanelHost']) ? $migrationDetails['cpanelHost'] : null,
                        "cpanelUser"        => isset($migrationDetails['cpanelUser']) ? $migrationDetails['cpanelUser'] : null,
                        "cpanelPass"        => isset($migrationDetails['cpanelPass']) ? $migrationDetails['cpanelPass'] : null,
                        "domain"            => $destinationURL,
                        "cpanelApiToken"    => isset($migrationDetails['cpanelApiToken']) ? $migrationDetails['cpanelApiToken'] : null,
                        "useApiToken"       => isset($migrationDetails['useApiToken']) ? $migrationDetails['useApiToken'] : null,
                        'currentURL'        => site_url(),
                        'destinationURL'    => isset($migrationDetails['URL']) ? $migrationDetails['URL'] : null,
                        'filename'          => $sampleArchiveFilename,
                    ]);

                    /**
                     * Assign the result to
                     */
                    $siteAccessed = $directDownload['message']->canDownload;

                    /**
                     * Remove the sample file
                     */
                    unlink(TRANSFERITO_ABSPATH . $sampleArchiveFilename);
                } else {
                    $siteAccessed = false;
                }
            }

		    /**
		     * Add the local flag to the ftp details
		     */
		    $migrationDetails['isLocal'] = $siteAccessed;

		    /**
		     * If the folder path is set and the transfer type is manual
		     * Then validate the
		     */
		    $additionalData = [];
		    if (isset($migrationDetails['folder_path']) && $migrationDetails['transferType'] === 'manual') {
			    $additionalData['folder_path'] = array_map('sanitize_text_field',  wp_unslash($migrationDetails['folder_path']));
		    }

		    /**
		     * Clean text fields before create migration request
		     */
		    $cleanMigration = array_map('sanitize_text_field', wp_unslash($migrationDetails));
		    $migrationPayload = array_merge($cleanMigration, $additionalData);
		    $migrationPayload['isLocal'] = $siteAccessed;

		    /**
		     * Create a migration and return a token
		     */
		    $createdMigration = $this->api->createMigration($migrationPayload);

		    /**
		     * Fail gracefully if there is an issue creating the migration
		     */
		    if ($createdMigration['code'] !== 200) {
                $message = (property_exists($createdMigration['message'], 'result'))
                    ? $createdMigration['message']->result
                    : 'We are unable to create your migration. If this issue persists, please contact support.';
			    throw new \Exception(stripslashes($message));
		    }

		    /**
		     * Set transients to use with the failure endpoint
		     */
		    set_transient('transferito_migration_token', $createdMigration['message']->token);
		    set_transient('transferito_migration_timestamp', $createdMigration['message']->timestamp);

		    /**
		     * Get the migration token
		     */
		    $migrationToken = $createdMigration['message']->token;

		    /**
		     * Final Domain
		     */
		    $finalDomain = $createdMigration['message']->domain;

		    /**
		     * If we can contact the site
		     * Then check the server requirements
		     */
		    if ($siteAccessed) {
			    /**
			     * Create the test file & token
			     */
			    $testFile = Config::createTestFile();

			    /**
			     * Check the destination server info
			     */
			    $serverRequirements = $this->api->checkDestinationServerRequirements([
				    'token'         => $migrationToken,
				    'timestamp'     => $createdMigration['message']->timestamp,
				    'fileURL'       => $testFile['url'],
				    'fileHash'      => $testFile['hash']
			    ]);

			    /**
			     * Fallback
			     * If the server req check fails
			     */
			    if ($serverRequirements['code'] !== 200) {
				    $uploadToS3 = true;
			    } else {
				    /**
				     * Check the site access again
				     * If it is a local environment - or if the module allowed is wget - Then default to S3
				     */
				    $uploadToS3 = !$serverRequirements['message']->pullDirect;
			    }
		    }

		    /**
		     * If we can not reach the site
		     * Upload directly to S3 bucket
		     */
		    if (!$siteAccessed) {
			    $uploadToS3 = true;
		    }

		    /**
		     * Set transient for backup status
		     */
		    set_transient('transferito_backup_status', [
			    'databaseBackupComplete'    => $excludeDatabase ? true : false,
			    'databaseExportComplete'    => $excludeDatabase ? true : false,
			    'codebaseBackupComplete'    => false,
			    'excludedDatabase'          => $excludeDatabase
		    ]);

		    /**
		     * Set transient with transfer detail
		     */
		    set_transient('transferito_transfer_detail', [
			    'isLocalEnv'        => $uploadToS3,
			    'selectedFolders'   => $selectedFolderEnabled,
			    'folders'           => $folderPaths,
			    'directory'         => $backupDirectory,
			    'token'             => $migrationToken,
			    'timestamp'         => $createdMigration['message']->timestamp,
			    'fromUrl'           => site_url(),
			    'newUrl'            => $finalDomain
		    ]);

		    /**
		     * Set the transient for the destination site URL
		     */
		    set_transient('transferito_final_destination_url', $finalDomain);

		    /**
		     * Available steps to pass to the template
		     */
		    $progressSteps = [
		    	'backupPrepare'         => true,
			    'backupInstallation'    => true,
			    'uploadBackup'          => $uploadToS3,
			    'downloadBackup'        => true,
			    'extractBackup'         => true,
			    'installDatabase'       => !$excludeDatabase,
			    'finalizeInstallation'  => true,
			    'completed'             => true
		    ];

		    /**
		     * Get the requirement transient
		     */
		    $transferitoRequirements = get_transient('transferito_requirements');

		    /**
		     * Site size info
		     */
		    $siteSizeInfo = get_transient('transferito_installation_size');
		    $siteSize = $siteSizeInfo ? $siteSizeInfo : [];

            /**
             * Push Migration detail event to telemetry
             */
            $this->telemetry->pushEvent('migrationDetails', [
                'uploadBackup'          => $uploadToS3,
                'localMigration'        => $uploadToS3,
                'databaseExcluded'      => $excludeDatabase,
                'selectedFolders'       => $selectedFolderEnabled,
                'migrationMethod'       => $migrationDetails['transferMethod'],
                'siteSize'              => isset($siteSize['totalSize']) ? $siteSize['totalSize'] : 0,
                'cPanelAPIToken'        => isset($migrationDetails['cpanelApiToken']) ? $migrationDetails['cpanelApiToken'] : false
            ]);

            /**
             * Push loadScreen event to telemetry
             */
            $this->telemetry->pushEvent('loadScreen', [
                'screenName'    => 'migrationInProgress'
            ]);

		    wp_send_json_success(array_merge([
                'message'           => '<strong>PLEASE DO NOT</strong> navigate away or reload this page while your migration is in process. Doing so will stop your migration.',
                'force'             => $forceUpload,
			    'useZipFallback'    => $transferitoRequirements['useZip'],
			    'created'           => true,
			    'excludeDatabase'   => $excludeDatabase,
			    'htmlTemplate'      => loadTemplate('parts/migration/progress/main',
                    array_merge($progressSteps, [ 'method' => $migrationDetails['transferMethod']] )
                )
		    ], $siteSize));

	    } catch (\Exception $exception) {
            /**
             * Push loadScreen event to telemetry
             */
            $this->telemetry->pushEvent('failedMigration', [
                'migrationStatus'   => 'prepareDownload',
                'errorMessage'      => $exception->getMessage()
            ]);
            $errorMessage = 'prepareDownload: ' . $exception->getMessage();
            $this->api->failedMigration($errorMessage);
		    delete_transient('transferito_transfer_detail');
		    wp_send_json_error($exception->getMessage(), 400);
	    }
    }

	public function prepareCodebase()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			$transferDetail = get_transient('transferito_transfer_detail');
			$fileListCreated = $this->codeBase->createFileList($transferDetail['selectedFolders'], $transferDetail['folders']);
			$zipPaths = $this->codeBase->createArchivePath();

            /**
             * Throw the error if the zip paths are not an array
             */
            if (is_string($zipPaths)) {
                throw new \Exception($zipPaths);
            }

			/**
			 * Set transient for zip creation paths
			 */
			set_transient('transferito_codebase_archive', $zipPaths);

			/**
			 * Get the site size
			 */
			$siteSizeInfo = get_transient('transferito_installation_size');

			wp_send_json_success(array_merge($fileListCreated, $siteSizeInfo));

		} catch(\Exception $exception) {
            /**
             * Push loadScreen event to telemetry
             */
            $this->telemetry->pushEvent('failedMigration', [
                'migrationStatus'   => 'prepareCodebase',
                'errorMessage'      => $exception->getMessage()
            ]);
            $errorMessage = 'prepareCodebase: ' . $exception->getMessage();
            $this->api->failedMigration($errorMessage);
			wp_send_json_error($exception->getMessage(), 400);
		}
	}

	public function addFilesToCodebase()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			/**
			 * Check to see if the client passed the add db flag flag is passed in to
			 */
			$dbExportFileListPath = isset($_POST['addDatabaseExports']) ? DIRECTORY_SEPARATOR . 'db_import' : '';
			$zipDetail = get_transient('transferito_codebase_archive');
			$jsonFileName = TRANSFERITO_UPLOAD_PATH . $dbExportFileListPath . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'file_list_' . $_POST['currentFileIndex'] . '.json';
			$jsonFile = file_get_contents($jsonFileName);

			if (!file_exists($jsonFileName)) {
				throw new \Exception('Something has gone wrong. We can not find the requested back up file list.');
			}

			if (!$jsonFile) {
				throw new \Exception('Something has gone wrong. We can not read requested back up file list.');
			}

			$files = json_decode($jsonFile);
			$addedToArchive = $this->codeBase->addFileToArchive($zipDetail['path'], $files);

            /**
             * If the response isn't truthy throw
             */
			if ($addedToArchive !== true) {
                throw new \Exception($addedToArchive);
            }

			wp_send_json_success($zipDetail);
		} catch(\Exception $exception) {
			wp_send_json_error($exception->getMessage(), 400);
		}
	}

	public function codebaseArchiveComplete()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			/**
			 * Get the related transients
			 */
			$backupStatus = get_transient('transferito_backup_status');
			$transferDetail = get_transient('transferito_transfer_detail');
			$zipDetail = get_transient('transferito_codebase_archive');

			/**
			 * Update the backup array
			 * Notification of the codebase archive completed
			 */
			$backupStatus['codebaseBackupComplete'] = true;

			/**
			 * Update the code path with the archive path or url
			 */
			$uploadS3 = $transferDetail['isLocalEnv'];
			$transferDetail['archive'] = $uploadS3 ? $zipDetail['path'] : $zipDetail['url'];

			/**
			 * Update the transients with the updated values
			 */
			set_transient('transferito_backup_status', $backupStatus);
			set_transient('transferito_transfer_detail', $transferDetail);

			wp_send_json_success([
				'codebaseArchived'  => true,
				'excludeDatabase'   => $backupStatus['excludedDatabase']
			]);

		} catch (\Exception $exception) {
			wp_send_json_error($exception->getMessage(), 400);
		}
	}

	public function prepareDatabase()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			/**
			 * Prepare a table map that allows the chunked db database export
			 */
			$tableMap = $this->dataBase->prepareTableMap();

			/**
			 * If the table map can not be created
			 * Throw the error
			 */
			if (!$tableMap) {
				throw new \Exception('We were in the process of preparing to back up your database but this has failed.');
			}

			/**
			 * Set the table map as a transient
			 */
			set_transient('transferito_database_table_map', $tableMap);

			/**
			 * Return an array
			 * Notifying the client of the table creation
			 */
			wp_send_json_success([ 'tableMapCreated' => true ]);

		} catch(\Exception $exception) {
            /**
             * Push loadScreen event to telemetry
             */
            $this->telemetry->pushEvent('failedMigration', [
                'migrationStatus'   => 'prepareDatabase',
                'errorMessage'      => $exception->getMessage()
            ]);
            $errorMessage = 'prepareDatabase: ' . $exception->getMessage();
            $this->api->failedMigration($errorMessage);
			wp_send_json_error($exception->getMessage(), 400);
		}
	}

	public function chunkedDBExport()
	{
    	try {
		    check_ajax_referer('prepare_migration_files', 'security');

		    /**
		     * Validate the first run flag and convert it to a boolean
		     */
		    $firstRun = filter_var($_POST['firstRun'], FILTER_VALIDATE_BOOLEAN);

		    /**
		     * Pull the export progress data
		     */
		    $getDBProgress = get_transient('transferito_db_export_progress');
		    $exportProgress = $getDBProgress ? $getDBProgress : [];

		    /**
		     * On the first run - No arguments needed
		     */
		    if ($firstRun) {
		    	$exportResult = $this->dataBase->chunkedDBExport();
		    }

		    /**
		     * If it isn't the first run
		     * Pass in the export progress
		     */
		    if (!$firstRun) {
			    $exportResult = $this->dataBase->chunkedDBExport(
			    	$exportProgress['fileIndex'],
				    $exportProgress['currentRowIndex'],
				    $exportProgress['tableIndex']
			    );
		    }

		    /**
		     * If the export fails - throw
		     */
		    if (!$exportResult) {
			    throw new \Exception('We are unable to backup a part of your database.');
		    }

		    /**
		     * Return an array
		     * Notifying the client of the status of the export
		     */
		    $mergedArray = array_merge($exportResult, $exportProgress, [ 'firstRun' => $firstRun ]);
		    wp_send_json_success($mergedArray);

	    } catch(\Exception $exception) {
		    wp_send_json_error($exception->getMessage(), 400);
	    }
	}

	public function databaseExportComplete()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			/**
			 * Get the related transients
			 */
			$backupStatus = get_transient('transferito_backup_status');

			/**
			 * Update the backup array
			 * Notification of the database export completion
			 */
			$backupStatus['databaseExportComplete'] = true;

			/**
			 * Get the requirement transient
			 */
			$transferitoRequirements = get_transient('transferito_requirements');

			/**
			 * Update the transients with the updated values
			 */
			set_transient('transferito_backup_status', $backupStatus);

			wp_send_json_success([
				'databaseExported'  => true ,
				'useZipFallback'    => $transferitoRequirements['useZip'],
			]);

		} catch (\Exception $exception) {
			wp_send_json_error($exception->getMessage(), 400);
		}
	}

	public function databaseRelocation()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			$this->dataBase->moveDatabaseFiles();

			wp_send_json_success([ 'moved'  => true ]);

		} catch (\Exception $exception) {
			wp_send_json_error(stripslashes('We could not move your database files'), 400);
		}
	}

	public function databaseRelocationCheck()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			/**
			 * Pull the pid
			 */
			$databaseMovePID = get_transient('transferito_database_relocation_pid');

			/**
			 * Get the site size
			 */
			$siteSizeInfo = get_transient('transferito_installation_size');

			wp_send_json_success([
				'completed' => checkJobHasCompleted($databaseMovePID),
				'siteInfo'  => $siteSizeInfo
			]);

		} catch (\Exception $exception) {
			wp_send_json_error(stripslashes('We can not move your database files'), 400);
		}
	}

	public function archiveCreation()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			/**
			 * Pull the transfer detail to get the selected folder information
			 */
			$transferDetail = get_transient('transferito_transfer_detail');

			/**
			 * Start the archive
			 */
			$zipPaths = $this->codeBase->createExecArchive($transferDetail['selectedFolders'], $transferDetail['folders']);

			/**
			 * Set transient for zip creation paths
			 */
			set_transient('transferito_codebase_archive', $zipPaths);

			wp_send_json_success([ 'archiveCreationStarted'  => true ]);

		} catch (\Exception $exception) {
            /**
             * Push loadScreen event to telemetry
             */
            $this->telemetry->pushEvent('failedMigration', [
                'migrationStatus'   => 'archiveCreation',
                'errorMessage'      => $exception->getMessage()
            ]);
            $errorMessage = 'archiveCreation: ' . $exception->getMessage();
            $this->api->failedMigration($errorMessage);
			wp_send_json_error(stripslashes('We can not create a backup of your site'), 400);
		}
	}

	public function archiveProgressCheck()
	{
		try {

			check_ajax_referer('prepare_migration_files', 'security');

			/**
			 * Pull the PID for the progress file
			 */
			$archiveCreationPID = get_transient('transferito_codebase_archive_pid');

			/**
			 * Get the file name
			 */
			$progressFile = TRANSFERITO_UPLOAD_PATH . '/.archive-process';

            /**
             * Get the archives
             */
            $archives = get_transient('transferito_codebase_archive');

            /**
             * Get info for the archive path
             */
            $archiveInfo = pathinfo($archives['path']);

            /**
             * Progress value
             */
            $progressValue = null;

            /**
             * Set the archive result
             */
            $archiveResult = [];

            /**
             * If the file is TAR
             * Read the amount of files
             */
            if ($archiveInfo['extension'] === 'tar') {
                /**
                 * Pull the information about the installation
                 */
                $installationInfo = get_transient('transferito_installation_size');

                /**
                 * Get the amount of lines in the progress file
                 */
                $file = new \SplFileObject($progressFile, 'r');
                $file->seek(PHP_INT_MAX);
                $progressAmount = $file->key() + 1;

                /**
                 * Calculate the percentage of the archive creation
                 */
                $progressValue = ($progressAmount / $installationInfo['amountOfFiles']) * 100;
            }

            /**
             * If the archive is ZIP
             * Calculate the progress of the archive creation based on the zip verbose file structure
             */
            if ($archiveInfo['extension'] === 'zip') {
                /**
                 * Get the last line of the progress file
                 */
                $lastLine = transferitoGetLastLine($progressFile);

                /**
                 * Get everything within the brackets
                 */
                preg_match("/\[(.*?)\]/", $lastLine, $match);

                /**
                 * If there is a match
                 * Process the logic to calculate the progress
                 */
                if (count($match) === 2) {
                    /**
                     * Remove all spaces from string
                     */
                    $cleanValue = str_replace(' ', '', $match[1]);

                    /**
                     * Split the progress values
                     */
                    $values = explode('/', $cleanValue);

                    /**
                     * Check that the 2 elements exist in the array
                     */
                    if (count($values) === 2) {
                        /**
                         * The amount completed via the zip
                         */
                        $completedAmount = preg_split('/(?<=[0-9])(?=[a-z]+)/i', $values[0]);

                        /**
                         * The amount remaining
                         */
                        $remainingAmount = preg_split('/(?<=[0-9])(?=[a-z]+)/i', $values[1]);

                        /**
                         * If the amount id 0 - dont count it yet
                         */
                        if ($completedAmount !== '0') {

                            $completedInBytes = transferitoConvertToBytes($completedAmount);
                            $remainingInBytes = transferitoConvertToBytes($remainingAmount);
                            $total = $completedInBytes + $remainingInBytes;
                            $progressValue = ($total === 0) ? 0 : round(($completedInBytes / $total) * 100);
                        }
                    }
                }
            }

			/**
			 * Check if the process has completed
			 */
			$completed = checkJobHasCompleted($archiveCreationPID);

			/**
			 * Remove the DB import directory
			 */
			if ($completed) {

				$transferDetail = get_transient('transferito_transfer_detail');

				/**
				 * Update the code path with the archive path or url
				 */
				$uploadS3 = $transferDetail['isLocalEnv'];
				$transferDetail['archive'] = $uploadS3 ? $archives['path'] : $archives['url'];

				/**
				 * Save the archive
				 */
				set_transient('transferito_transfer_detail', $transferDetail);

				/**
				 * Remove the import directory
				 */
				$this->removeDBImportDirectory();

				/**
				 * Get the backup status
				 */
				$backupStatus = get_transient('transferito_backup_status');

				/**
				 * Assign the flags to variables
				 */
				$databaseExcluded = $backupStatus['excludedDatabase'];

				/**
				 * Result of the response when the DB is excluded
				 */
				if ($databaseExcluded) {
					$archiveResult = $this->archiveCompletionResponse(true, false);
				}

				/**
				 * Result of the response when the DB is not excluded
				 */
				if (!$databaseExcluded) {
					$archiveResult = $this->archiveCompletionResponse(true, true);
				}
			}

			wp_send_json_success(array_merge([
				'completed' => $completed,
				'progress'  => $progressValue
			], $archiveResult));

		} catch (\Exception $exception) {
			wp_send_json_error(stripslashes('We can not get the progress of your backup'), 400);
		}
	}

	public function archiveDBExport()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			/**
			 * Create the DB file list
			 */
			$exportFileList = $this->dataBase->createFileList();

			/**
			 * Throw if any issues
			 */
			if (!$exportFileList) {
				throw new \Exception('We are unable to create the list of database exports that wll be added to your backup.');
			}

			 wp_send_json_success($exportFileList);

		} catch (\Exception $exception) {
			wp_send_json_error($exception->getMessage(), 400);
		}
	}

	public function checkArchiveCompletion()
	{
		try {
			check_ajax_referer('prepare_migration_files', 'security');

			/**
			 * Get the backup status
			 */
			$backupStatus = get_transient('transferito_backup_status');

			/**
			 * Check that there is a backup status available
			 */
			if (!$backupStatus) {
				throw new \Exception('There was an error retrieving your backup information');
			}

			/**
			 * Assign the flags to variables
			 */
			$databaseExcluded = $backupStatus['excludedDatabase'];

			/**
			 * Default the archive result
			 */
			$archiveResult = $this->archiveCompletionResponse(false, false);

			/**
			 * Result of the response when the DB is excluded
			 */
			if ($databaseExcluded && $backupStatus['codebaseBackupComplete']) {
				$archiveResult = $this->archiveCompletionResponse(true, false);
			}

			/**
			 * Result of the response when the DB is not excluded
			 */
			if (!$databaseExcluded && $backupStatus['codebaseBackupComplete'] && $backupStatus['databaseExportComplete']) {
				$archiveResult = $this->archiveCompletionResponse(true, true);
			}

			/**
			 * Notify the client whether or not the migration should be started or the db exports need to be zipped
			 */
			wp_send_json_success($archiveResult);

		} catch (\Exception $exception) {
			wp_send_json_error($exception->getMessage(), 400);
		}
	}

	public function initiateUpload()
	{
		check_ajax_referer('start_upload', 'security');

		$transferDetail = get_transient('transferito_transfer_detail');

		try {
			scandir(TRANSFERITO_UPLOAD_PATH);

			/**
			 * Create an array to hold the backups
			 */
			$backup = [];

			/**
			 * Start the upload for the codebase archive
			 */
            try {
                $codebaseUploadId = $this->upload->startUpload();
            } catch(\Exception $ex) {
                throw new \Exception($ex->getMessage());
            }

			/**
			 * Add all the relevant detail to the $backup array
			 */
			$backup['archive'] = [
				'type'          => 'codebase',
				'parts'         => $this->getFileParts($transferDetail['archive']),
				'uploadId'      => $codebaseUploadId
			];

			/**
			 * Send response
			 */
			wp_send_json_success([
				'backup'    => $backup
			]);

		} catch (\Exception $exception) {
            /**
             * Push failedMigration event to telemetry
             */
            $this->telemetry->pushEvent('failedMigration', [
                'migrationStatus'   => 'AWSInitiateUpload',
                'errorMessage'      => $exception->getMessage()
            ]);
            $errorMessage = 'AWSInitiateUpload: ' . $exception->getMessage();
			$this->api->failedMigration($errorMessage);
			wp_send_json_error($errorMessage, 400);
		}

	}

	public function uploadChunk()
	{
		try {
			$transferDetail = get_transient('transferito_transfer_detail');

			/**
			 * Get the correct file
			 */
			$filePath = $transferDetail['archive'];

			/**
			 * Get the part number
			 */
			$partNumber = filter_var($_POST['partNumber'], FILTER_VALIDATE_INT);

			/**
			 * Get the chunk size
			 */
			$chunkSize = Config::getChunkSize();

			/**
			 * Set FilePointer
			 */
			$filePointer = ($partNumber - 1) * $chunkSize;

			/**
			 * Get the file
			 */
			$file = fopen($filePath , 'rb');

			/**
			 * Set the file pointer
			 */
			fseek($file, $filePointer);

			/**
			 * Get the chunk
			 */
			$chunk = fread($file, $chunkSize);

			/**
			 * Close the file
			 */
			fclose($file);

			unset($file);

			/**
			 * Send the chunk to the API
			 */
            try {
                $chunkUploaded = $this->upload->uploadChunk($partNumber, $chunk);
            } catch(\Exception $ex) {
                throw new \Exception($ex->getMessage());
            }

			unset($chunk);


			/**
			 * Send response
			 */
			wp_send_json_success([
				'uploaded'      => true,
				'partNumber'    => $partNumber
			]);

		} catch (\Exception $exception) {
            /**
             * Push failedMigration event to telemetry
             */
            $this->telemetry->pushEvent('failedMigration', [
                'migrationStatus'   => 'AWSUploadChunk',
                'errorMessage'      => $exception->getMessage()
            ]);
            $errorMessage = 'AWSUploadChunk: ' . $exception->getMessage();
            $this->api->failedMigration($errorMessage);
			wp_send_json_error('There was an error uploading your chunk', 400);
		}
	}

	public function completeUpload()
	{
		try {
			/**
			 * Pull the transfer detail
			 */
			$transferDetail = get_transient('transferito_transfer_detail');

			/**
			 * Call the upload complete end point
			 */
            try {
                $uploadInfo = $this->upload->completeUpload();
            } catch(\Exception $ex) {
                throw new \Exception($ex->getMessage());
            }

			/**
			 * Assign the upload to the migration
			 */
			$completeUpload = $this->api->completeUpload([
				'filename'  => $uploadInfo['path'],
				'token'     => $transferDetail['token'],
				'timestamp' => $transferDetail['timestamp']
			]);

			/**
			 * If the upload completion failed
			 */
			if ($completeUpload['code'] !== 200) {
				throw new \Exception(stripslashes('Your upload can not be assigned to your migration.'));
			}

			/**
			 * Set the publicly accessible path for the backup
			 */
			$transferDetail['archive'] = $uploadInfo['URL'];

			/**
			 * Set transient with transfer detail
			 */
			set_transient('transferito_transfer_detail', $transferDetail);

			/**
			 * Send response
			 */
			wp_send_json_success([
				'htmlTemplate'     => loadTemplate('parts/loading', [
					'showMigrationImage'    => true,
					'mainMessage'           => stripslashes('We have successfully backed up your WordPress installation'),
					'secondaryMessage'      => 'We are currently migrating your site to your new destination',
				]),
				'securityKey'   => wp_create_nonce('start_migration')
			]);

		} catch (\Exception $exception) {
            /**
             * Push failedMigration event to telemetry
             */
            $this->telemetry->pushEvent('failedMigration', [
                'migrationStatus'   => 'AWSCompleteUpload',
                'errorMessage'      => $exception->getMessage()
            ]);
            $errorMessage = 'AWSCompleteUpload: ' . $exception->getMessage();
            $this->api->failedMigration($errorMessage);

            wp_send_json_error('There was an error completing your upload', 400);
		}
	}

    public function startMigration()
    {
        check_ajax_referer('start_migration', 'security');

        $settings = get_option('transferito_settings_option');
        $chunkSize = isset($settings['transferito_chunk_size']) ? $settings['transferito_chunk_size'] : '50';
        $transferDetail = get_transient('transferito_transfer_detail');
        $installationInfo = get_transient('transferito_installation_size');
        $dbCharsetInfo = get_transient('transferito_database_charset_info');
        delete_transient('transferito_transfer_detail');
        delete_transient('transferito_database_charset_info');

	    /**
	     * Create the payload to send to the API
	     */
	    $startMigrationPayload = [
	        'chunkSize'         => $chunkSize,
	        'charset'           => $dbCharsetInfo['actualCharset'],
	        'configCharset'     => $dbCharsetInfo['configCharset'],
		    'archiveFileAmount' => $installationInfo['amountOfFiles'],
		    'isLocal'           => $transferDetail['isLocalEnv'],
		    'archive'           => $transferDetail['archive'],
		    'backupDirectory'   => $transferDetail['directory'],
		    'token'             => $transferDetail['token'],
		    'timestamp'         => $transferDetail['timestamp'],
		    'fromUrl'           => $transferDetail['fromUrl'],
		    'currentPath'       => ABSPATH
	    ];

        /**
         * Send files to API
         */
        $startMigrationRequest = $this->api->startMigration($startMigrationPayload);
	    $statusCode = $startMigrationRequest['code'];
	    $response = $startMigrationRequest['message'];

	    /**
	     * If upload notification has been successful
	     * Add flags stop backup removal
	     */
	    if ($statusCode === 200) {
		    set_transient('transferito_ignore_cleanup', true, 12 * HOUR_IN_SECONDS);
		    set_transient('transferito_cleanup_after_completion', true, 12 * HOUR_IN_SECONDS);
	    }

        /**
         * Return response based on status
         */
        if ($statusCode === 200) {
            $emptyKeys = get_transient('transferito_empty_keys');
            $availableTransfers = get_transient('transferito_has_available_transfers');
            $message = ($emptyKeys || !$availableTransfers)
                ? 'If you navigate away from the site, your migration will continue in the background. As you\'re not a premium member, we advise that you stay on this page until your migration has completed.'
                : 'If you navigate away from the site, your migration will continue in the background. You will be sent an email once your migration has completed.';
            wp_send_json_success([
                'token'     => $response->token,
                'message'   => $message
            ]);
        } else {
        	$errorMessage = is_array($response) ? 'There has been an error starting your migration' : stripslashes($response);
            wp_send_json_error($errorMessage, $statusCode);
        }
    }

    public function cleanUp($url = null)
    {
	    $url = get_transient('transferito_final_destination_url');
	    $hasError = $_POST['hasError'];
	    $metadata = $_POST['metadata'];
	    $errors = isset($_POST['errors']) ? $_POST['errors'] : null;
    	$failureList = [
		    'CPANEL_CHECK_FAILED'       => 'There has been an issue checking your URL',
			'SWITCH_METHOD_FAILED'      => 'There has been an issue switching to the new migration mode',
		    'FAILED_BACKUP_CHECK'       => 'There has been an error processing a backup of your WordPress site',
		    'FAILED_CREATE_BACKUP'      => 'There has been an error creating a backup of your WordPress site',
		    'FAILED_REMOVE_BACKUP'      => 'There has been an error removing a backup of your WordPress site',
		    'FAILED_STARTING_MIGRATION' => 'There has been an issue starting your migration',
		    'ERROR_GETTING_STATUS'      => 'There has been an issue getting the status of your migration.',
		    'UPLOAD_START_FAILURE'      => 'We have been unable to start uploading your backup to our servers',
            'UPLOAD_CHUNK_FAILURE'      => 'An error has occurred whilst uploading your backup to our servers',
            'UPLOAD_COMPLETION_FAILURE' => 'We are unable to complete the upload of your backup to our servers',
		    'USE_CUSTOM_ERROR_MESSAGE'  => $errors && isset($errors['data']) ? $errors['data'] : 'There has been an issue processing your migration',
	    ];
	    $validError = in_array($hasError, array_keys($failureList));


    	try {
            clearstatcache();

            if (file_exists(TRANSFERITO_UPLOAD_PATH)) {
                /**
                 * Check if there has been an error archiving
                 */
                $this->checkArchiveError();

                /**
                 * Remove everything in the transferito directory
                 */
                $this->purgeDirectory(TRANSFERITO_UPLOAD_PATH);
		    }

		    /**
		     * Remove all instances of the db import directory
		     */
		    $this->removeDBImportDirectory();

		    /**
		     * If the error exists
		     */
		    if ($validError) {
			    $this->api->failedMigration($failureList[$hasError]);
			    $htmlTemplate = [
                    'error'     => $failureList[$hasError],
                    'failed'    => true,
                ];
		    } else {
			    $htmlTemplate = loadTemplate('parts/migration/completed', [
			        'url'       => $url,
                    'metadata'  => $metadata
                ]);
		    }
	    } catch (\Exception $exception) {
		    if ($validError) {
			    $this->api->failedMigration($errors);
                $htmlTemplate = [
                    'error'     => $failureList[$hasError],
                    'failed'    => true,
                    'secondary' => stripslashes('We have not been able to remove your backup. Please remove it manually.')
                ];
		    } else {
			    $this->api->failedMigration($failureList['FAILED_REMOVE_BACKUP']);
			    $htmlTemplate = loadTemplate('parts/migration/completed', [ 'url' => $url, 'error' => 'FAILED_BACKUP_REMOVAL' ]);
		    }
	    }

        $this->enableAutoPrependOption();
        $this->enableWPObjectCache();
	    $this->removeTransferitoTransients();

	    wp_send_json_success([ 'htmlTemplate'  => $htmlTemplate ]);
    }

	private function selectCorrectTemplate(array $additionalTemplateData = [])
	{
		$transferitoRequirements = get_transient('transferito_requirements');

        /**
         * Get the transient for first time plugin load
         */
        $hideQuickStartGuide = get_transient('transferito_hide_quick_start_guide');

		$siteSize = '250MB';

		/**
		 * Create default template data
		 */
		$templateData = array_merge(
			[
				'metRequirements'   => $transferitoRequirements['metRequirements'],
				'size'              => $siteSize,
                'hideQuickStart'    => $hideQuickStartGuide,
                'mainMessage'       => "We're happy to let you know. Your site is <b>{$siteSize}</b>, which is smaller than our <b>250MB</b> size limit.",
				'secondaryMessage'  => 'To start your migration, please choose a migration method'
			],
			$additionalTemplateData
		);

		try {
			$siteDetails = getDirectorySize(TRANSFERITO_ABSPATH);
			$siteSize = $siteDetails['amount'] . '' . $siteDetails['factor'];

			/**
			 * Inform the API Wrapper of the type of user
			 */
			$this->api->setFreeUser();
			$this->api->setMaxSizeExceeded($siteDetails['maxSizeExceeded']);

			/**
			 * Update template data
			 */
			$templateData['size'] = $siteSize;
			$templateData['mainMessage'] = (isset($templateData['noTransfersLeft']))
                ? "You don't have any migrations left. You'll only be able to migrate sites smaller than our <b>250MB</b> size limit. We're happy to let you know your site is <b>{$siteSize}</b>."
                : "We're happy to let you know. Your site is <b>{$siteSize}</b>, which is smaller than our <b>250MB</b> size limit.";

            /**
             * Update legend message
             */
            if ($siteDetails['maxSizeExceeded']) {
                $templateData['mainMessage'] = "Your site is <b>{$siteSize}</b>, which is larger than our <b>250MB</b> size limit.";

                /**
                 * Push upgradeRequired event to telemetry
                 */
                $this->telemetry->pushEvent('premiumUpgradeRequired', [
                    'exceededFreeTierSize'  => 'yes',
                ]);
            }

			/**
			 * Load the correct  template based on the site size
			 */
			$htmlTemplate = ($siteDetails['maxSizeExceeded'])
				? loadTemplate('parts/choose-upgrade-method', $templateData)
				: loadTemplate('parts/migration/cpanel-check', $templateData);

		} catch(\Exception $exception) {
            /**
             * Push upgradeRequired event to telemetry
             */
            $this->telemetry->pushEvent('premiumUpgradeRequired', [
                'exceededFreeTierSize'  => 'yes',
            ]);

			$htmlTemplate = loadTemplate('parts/choose-upgrade-method', $templateData);
		}

		return [ 'htmlTemplate'  => $htmlTemplate, 'additionalData' => $templateData ];
	}

	private function getFileParts($filePath)
	{
		sleep(5);
		getDirectorySize(TRANSFERITO_ABSPATH);
		clearstatcache();
		$fileInfo = stat($filePath);
		$archiveSize = $fileInfo['size'];
		$chunkSize = Config::getChunkSize();
		return ceil($archiveSize / $chunkSize);
	}

	private function freshStart() {
    	try {
            clearstatcache();

            $this->enableAutoPrependOption();
            $this->enableWPObjectCache();
            $this->removeTransferitoTransients();
			$this->removeDBImportDirectory();

		    /**
		     *  Check if an unfinished migration still exists
		     */
		    if (file_exists(TRANSFERITO_UPLOAD_PATH)) {
                /**
                 * Check if there has been an error archiving
                 */
                $this->checkArchiveError();

                /**
                 * Remove everything in the transferito directory
                 */
                $this->purgeDirectory(TRANSFERITO_UPLOAD_PATH);
		    }
	    } catch(\Exception $exception) {
			// do nada - for now
	    }
	}

	private function removeTransferitoTransients()
	{
		delete_transient('transferito_size_size_in_bytes');
		delete_transient('transferito_readable_size_size_in_bytes');
		delete_transient('transferito_request_fallback');
		delete_transient('transferito_destination_url');
		delete_transient('transferito_backup_status');
		delete_transient('transferito_transfer_detail');
		delete_transient('transferito_final_destination_url');
		delete_transient('transferito_codebase_archive');
		delete_transient('transferito_database_table_map');
		delete_transient('transferito_ignore_cleanup');
		delete_transient('transferito_cleanup_after_completion');
		delete_transient('transferito_db_export_progress');
		delete_transient('transferito_migration_token');
		delete_transient('transferito_migration_timestamp');
		delete_transient('transferito_migration_domain');
		delete_transient('transferito_migration_unchanged_domain');
		delete_transient('transferito_transfer_method');
		delete_transient('transferito_cpanel_allowed');
		delete_transient('transferito_cpanel_domain');
		delete_transient('transferito_manual_server_detail');
		delete_transient('transferito_use_backup_fallback');
		delete_transient('transferito_installation_size');
		delete_transient('transferito_requirements');
		delete_transient('transferito_database_relocation_pid');
		delete_transient('transferito_codebase_archive_pid');
		delete_transient('transferito_upload_information');
		delete_transient('transferito_database_charset_info');
        delete_transient('transferito_cpanel_auth_details');
        delete_transient('transferito_empty_keys');
        delete_transient('transferito_has_available_transfers');
        delete_transient('transferito_database_detail_completed');
        delete_transient('transferito_cpanel_auth_details_completed');
        delete_transient('transferito_archive_extension');
        delete_transient('transferito_cpanel_domain_selection');
        delete_transient('transferito_object_cache_filter_present');
        delete_transient('transferito_auto_prepend_option_disabled');

    }

	private function archiveCompletionResponse($backupsCompleted, $zipDBExport)
	{
		/**
		 * Get the Transfer Detail
		 */
		$transferDetail = get_transient('transferito_transfer_detail');

		/**
		 * Get the local Env flag
		 */
		$localEnv = $transferDetail['isLocalEnv'];

		/**
		 * Information for the client
		 */
		$information = null;

		/**
		 * If the zip DB Export is not needed
		 */
		if ($backupsCompleted) {
			/**
			 * If the site can not be reached - Then upload the archive
			 */
			if ($localEnv) {
				$information = [
					'htmlTemplate'      => loadTemplate('parts/migration/upload-progress', [
						'title'                 => 'Upload in progress. Please wait...',
						'mainMessage'           => stripslashes('We are uploading a backup of your site to our secure servers.'),
						'secondaryMessage'      => stripslashes('Please wait.. This may take a few minutes, do not close this window or refresh the page'),
					]),
					'securityKey'               => wp_create_nonce('start_upload'),
					'uploadFiles'               => true,
				];
			}

			/**
			 * If the site can be reached - Then pull direct
			 */
			if (!$localEnv) {
				$information = [
					'htmlTemplate'     => loadTemplate('parts/loading', [
						'showMigrationImage'    => true,
						'mainMessage'           => stripslashes('We have successfully backed up your WordPress installation'),
						'secondaryMessage'      => 'We are currently migrating your site to your new destination',
					]),
					'securityKey'               => wp_create_nonce('start_migration'),
					'uploadFiles'               => false,
				];
			}
		}

		return [
			'backupComplete'    => $backupsCompleted,
			'zipDatabaseExport' => $zipDBExport,
			'information'       => $information
		];
	}

	private function removeDBImportDirectory()
	{
		$importDirectory = TRANSFERITO_ABSPATH . 'transferito_import';

		/**
		 * If the Directory exists
		 */
		if (file_exists($importDirectory)) {
			array_map('unlink', array_filter(glob($importDirectory . '/*')));
			rmdir($importDirectory);
		}
	}

    private function purgeDirectory($directory)
    {
        if (!$directory) {
            return true;
        }

        clearstatcache();

        $it = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        clearstatcache();

        rmdir($directory);

        return true;
    }

    private function checkArchiveError()
    {
        $archiveErrorPath = TRANSFERITO_UPLOAD_PATH . DIRECTORY_SEPARATOR . '.archive-error';
        $archiveErrorRootPath = TRANSFERITO_ABSPATH . '.archive-error';
        $hasErrors = strlen(file_get_contents($archiveErrorPath)) > 0;

        /**
         * If the file has errors
         * Then move the archive error file to the root directory
         */
        if ($hasErrors) {
            rename($archiveErrorPath, $archiveErrorRootPath);
        }
    }

    private function disableAutoPrependOption()
    {
        $autoPrependOptionData = getPrependOptionNameData();
        $userIniFilePath = $autoPrependOptionData['path'];
        $userIniFileExists = file_exists($userIniFilePath);

        /**
         * Only proceed with the user.ini modification if the file exists
         */
        if ($userIniFileExists) {
            try {

                $prependOptionName = $autoPrependOptionData['text'];
                $commentedPrependOptionName = $autoPrependOptionData['commented'];
                $userIniFile = file_get_contents($userIniFilePath);
                $optionExists = str_contains($userIniFile, $prependOptionName);

                /**
                 * If there isn't an auto_prepend option throw a graceful error
                 */
                if (!$optionExists) {
                    throw new \Exception('AUTO_PREPEND_MODIFICATION_NOT_REQUIRED');
                }

                /**
                 * Check to see if the option has been commented previously
                 */
                $optionPreviouslyCommentedExists = str_contains($userIniFile, $commentedPrependOptionName);

                /**
                 * If a comment doesn't exist - modify the file
                 */
                if (!$optionPreviouslyCommentedExists) {
                    $modifiedUserIni = str_replace($prependOptionName, $commentedPrependOptionName, $userIniFile);
                    file_put_contents($userIniFilePath, $modifiedUserIni);
                }

                set_transient('transferito_auto_prepend_option_disabled', true);

            } catch(\Exception $exception) {
                set_transient('transferito_auto_prepend_option_disabled', false);
            }
        }
    }

    private function disableWPObjectCache()
    {
        $objectCacheFilterData = getObjectCacheFilterData();

        /**
         * Only make this change if the function file exists
         */
        if (file_exists($objectCacheFilterData['path'])) {
            try {
                $cacheFilterExists = str_contains(file_get_contents($objectCacheFilterData['path']), $objectCacheFilterData['text']);

                /**
                 * If the filter isn't already in the PHP file
                 */
                if (!$cacheFilterExists) {
                    $filterModified = PHP_EOL . $objectCacheFilterData['text'] . PHP_EOL;
                    file_put_contents($objectCacheFilterData['path'], $filterModified, FILE_APPEND | LOCK_EX);
                }

                /**
                 * Set the transient, so we can decide whether we need to revert the file
                 */
                set_transient('transferito_object_cache_filter_present', true);
            } catch (\Exception $exception) {
                set_transient('transferito_object_cache_filter_present', false);
            }
        }
    }

    private function enableAutoPrependOption()
    {
        $autoPrependOptionStatus = get_transient('transferito_auto_prepend_option_disabled');

        /**
         * Only enable the option if the file transient status if truthy
         */
        if ($autoPrependOptionStatus) {
            $autoPrependOptionData = getPrependOptionNameData();

            /**
             * Check the file exists
             */
            if (file_exists($autoPrependOptionData['path'])) {
                try {
                    $userIniFile = file_get_contents($autoPrependOptionData['path']);
                    $commentedOptionExists = str_contains($userIniFile, $autoPrependOptionData['commented']);

                    /**
                     * Check to see that the commented option is present
                     */
                    if ($commentedOptionExists) {
                        $modifiedUserIniFile = str_replace($autoPrependOptionData['commented'], $autoPrependOptionData['text'], $userIniFile);
                        file_put_contents($autoPrependOptionData['path'], $modifiedUserIniFile);
                    }
                } catch(\Exception $exception) {
                    // handle gracefullu
                }
            }
        }
    }

    private function enableWPObjectCache()
    {
        $wpObjectCacheStatus = get_transient('transferito_object_cache_filter_present');

        /**
         * Only enable if the status is truthy
         */
        if ($wpObjectCacheStatus) {
            $objectCacheFilterData = getObjectCacheFilterData();

            /**
             * Check the file exists
             */
            if (file_exists($objectCacheFilterData['path'])) {
                try {
                    $functionsFile = file_get_contents($objectCacheFilterData['path']);
                    $cacheFilterExists = str_contains($functionsFile, $objectCacheFilterData['text']);

                    /**
                     * If the filter lives in the functions file, remove it
                     */
                    if ($cacheFilterExists) {
                        $modifiedFunctionsFile = str_replace($objectCacheFilterData['text'], '', $functionsFile);
                        file_put_contents($objectCacheFilterData['path'], $modifiedFunctionsFile);
                    }
                } catch(\Exception $exception) {
                    // Fail gracefully
                }
            }
        }
    }

}
