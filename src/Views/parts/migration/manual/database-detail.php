<input type="hidden" id="manualMigrationDatabaseDetail" value="<?php echo wp_create_nonce('manual_migration_database_detail'); ?>">

<div class="transferito__three-columns">
    <div class="transferito__column transferito__navigation-column">
        <?php echo loadTemplate( 'parts/migration/navigation', [
            'destinationURL'        => 'completed',
            'chooseMethod'          => 'completed',
            'ftpAuthentication'     => 'completed',
            'selectDirectory'       => 'completed',
            'databaseAuthentication'=> 'active',
            'startMigration'        => 'disabled'
        ]); ?>
    </div>
    <div class="transferito__column transferito__main-column">
        <div class="transferito-text__h1">Destination Server Database Details</div>
        <div class="transferito__content-container">
            <div class="transferito-database-authentication">

                <div class="transferito-database-authentication__checkbox">
                    <label class="transferito-input__checkbox--label transferito-text__p1--bold" for="toggleSelectedFolders">
                        <input type="checkbox"
                               class="transferito-input__checkbox transferito-form-element"
                               id="excludeDatabase"
                               name="exclude_database_transfer">
                        Only transfer your website files to your new server
                    </label>
                    <div class="transferito-database-authentication__checkbox--content transferito-text__small">
                        If you're transferring your entire website & database, leave this unchecked!
                    </div>
                </div>

                <div class="transferito-database-authentication__input-fields">


                    <div class="transferito-database-authentication__title transferito-text__p1--bold">
                        <span class="transferito-input__required">*</span>
                        Enter your Database Name
                    </div>
                    <div class="transferito-database-authentication__input">
                        <input type="text"
                               name="dbName"
                               id="field__serverDetailDatabaseName"
                               value="<?php echo isset($data['detail']['dbName']) ? $data['detail']['dbName'] : ''; ?>"
                               class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin">
                    </div>

                    <div class="transferito-database-authentication__title transferito-text__p1--bold">
                        <span class="transferito-input__required">*</span>
                        Enter your Database Username
                    </div>
                    <div class="transferito-database-authentication__input">
                        <input type="text"
                               name="dbUser"
                               id="field__serverDetailDatabaseUser"
                               value="<?php echo isset($data['detail']['dbUser']) ? $data['detail']['dbUser'] : ''; ?>"
                               class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin">
                    </div>

                    <div class="transferito-database-authentication__title transferito-text__p1--bold">
                        <span class="transferito-input__required">*</span>
                        Enter your Database Password
                    </div>
                    <div class="transferito-database-authentication__input">
                        <input type="text"
                               name="dbPass"
                               id="field__serverDetailDatabasePassword"
                               value="<?php echo isset($data['detail']['dbPass']) ? $data['detail']['dbPass'] : ''; ?>"
                               class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin">
                    </div>

                    <div class="transferito-database-authentication__title transferito-text__p1--bold">
                        Enter your Database Host
                    </div>
                    <div class="transferito-domain-selection__content transferito-text__small">
                        The database host is optional, if you do not have one. Please leave this field empty
                    </div>
                    <div class="transferito-database-authentication__input">
                        <input type="text"
                               name="dbHost"
                               id="field__serverDetailDatabaseHost"
                               value="<?php echo isset($data['detail']['dbHost']) ? $data['detail']['dbHost'] : ''; ?>"
                               class="transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin">
                    </div>
                </div>

                <div class="transferito-database-authentication__action-buttons">
                    <button id="routeToDirectorySelector" data-screen-route="directorySelector" class="transferito-button transferito-button__secondary transferito-button--small transferito__screen-routing">BACK</button>
                    <button id="manualServerMigrationStart" class="transferito-button transferito-button__primary transferito-button--small transferito__start-manual-migration" <?php echo $data['completed'] ? '' : 'disabled'; ?>>START MIGRATION</button>
                </div>
            </div>
        </div>
    </div>
    <div class="transferito__column transferito__pro-tip-column">
        <?php echo loadTemplate( 'parts/migration/pro-tip', [
            'textBox' => [
                "content" => "We validate your destination server's database details before we start your migration. Watch our video on how to find and use your destination server's database details.",
                "link" => [
                    "anchorText" => "Click here to see how we use your database details",
                    "modalName" => "findCreateDatabaseDetails"
                ]
            ],
            'faqs' => [
                [
                    "anchorText"    => "What is a Database?",
                    "modalName"     => "whatIsDatabase"
                ],
                [
                    "anchorText"    => "How can I create Database details on my destination server?",
                    "modalName"     => "createDatabaseDestinationServer"
                ],
                [
                    "anchorText"    => "How do I add my Database Port?",
                    "modalName"     => "addDatabasePort"
                ],
                [
                    "anchorText"    => "Where can I find my Database Host?",
                    "modalName"     => "findDatabaseHost"
                ],
                [
                    "anchorText"    => "Where can I find my Database Name?",
                    "modalName"     => "findDatabaseName"
                ],
                [
                    "anchorText"    => "Where can I find my Database User?",
                    "modalName"     => "findDatabaseUser"
                ],
                [
                    "anchorText"    => "Where can I find my Database Password?",
                    "modalName"     => "findDatabasePassword"
                ]
            ]
        ]); ?>
    </div>
</div>

<div id="errorFailedDatabaseAuth" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/notice', [
        'image'             => 'failed-db-auth',
        'type'              => 'error',
        'messageTitle'      => 'Database Connection Failed',
        'message'           => 'There has been an error connecting to your database. Please check your database details and try again.',
        'closeButton'       => true,
        'additionalInfo'    => 'If you are still facing this issue our migration specialists are happy to help you resolve this issue.',
        'supportLink'       => [
            'anchorText'    => 'Create Support Ticket',
            'url'           => 'https://wordpress.org/support/plugin/transferito/'
        ]
    ]); ?>
</div>

<div id="findCreateDatabaseDetails" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Why do we need your Database details?',
        'title'             => 'Why do we need your Database details?',
        'mainContent'       => 'This video will give you an overview of how Transferito uses your database details.',
        'videoID'           => 'bw1cpwIqzi8',
    ]); ?>
</div>
<div id="whatIsDatabase" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'What is a Database?',
        'title'             => 'What is a Database?',
        'mainContent'       => 'This video is a quick introduction on what a database is and why it is used by WordPress.',
        'videoID'           => 'iFXEZ7s3Rb0',
    ]); ?>
</div>
<div id="createDatabaseDestinationServer" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'How can I create my Database details?',
        'title'             => 'How can I create my Database details?',
        'mainContent'       => 'These videos will show you how to create your Database details on your destination server.',
        'videoID'           => 'Hr5x1g4DZvg',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => 'MxBZtzYwPHQ'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'n_fOY0HTNMc'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => 'H92YpNA6Hd8'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'EhzDmr--a4o'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => 'N8HCtKI-cgc'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => 'V3QSmfJjJmA'
                ],
            ],
        ]
    ]); ?>
</div>
<div id="addDatabasePort" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'How do I add my Database port?',
        'title'             => 'How do I add my Database port?',
        'mainContent'       => 'A quick video showing you how to use your Database Port with your Database Host.',
        'videoID'           => 'vxiN7M7UXs4',
    ]); ?>
</div>
<div id="findDatabaseHost" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find my Database host?',
        'title'             => 'Where can I find my Database host?',
        'mainContent'       => 'A quick video showing you how to find your Database Host for your destination servers hosting provider.',
        'videoID'           => 'CtPjIBc188k',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => 'OiCvtDD4UKI'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'KCHgi9e7_J8'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => 'cQ0bMM32bMQ'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'DR68yUoWv10'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => 'BTrDXL1wq08'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => 'yyzMlq_0MX4'
                ],
            ],
        ]
    ]); ?>
</div>
<div id="findDatabaseName" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find my Database name?',
        'title'             => 'Where can I find my Database name?',
        'mainContent'       => 'A quick video showing you how to find your Database Name for your destination servers hosting provider.',
        'videoID'           => 'LW-fFRC2dms',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => '5H9Tsb8DekE'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'Os1eb74uSkY'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => 'QyUcAr7FG58'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'UO7RaSijncc'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => 'lPaWVAlo-C4'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => 'V9hso3phpm8'
                ],
            ],
        ]
    ]); ?>
</div>
<div id="findDatabaseUser" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find my Database username?',
        'title'             => 'Where can I find my Database username?',
        'mainContent'       => 'A quick video showing you how to find your Database Username for your destination servers hosting provider.',
        'videoID'           => 'gghxgxi3lv8',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => 'N2oaozlEJ-M'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'P9qdiCgC4Qg'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => '6QDObsrXIN4'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'Eqssnm0RV3g'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => 'ziLz2Ul3ltc'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => '0a5g4gw_WFM'
                ],
            ],
        ]
    ]); ?>
</div>
<div id="findDatabasePassword" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find my Database password?',
        'title'             => 'Where can I find my Database password?',
        'mainContent'       => 'A quick video showing you how to find your Database Name for your destination servers hosting provider.',
        'videoID'           => 'r77F_ofHDLE',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => 'HtY8FGMFj38'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'xBi2FJE2nK0'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => '-meHqoqRYd8'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'ENnWdWrDDCo'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => 'zkkEg6rNP7g'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => 'MwPJW8oCt4M'
                ],
            ],
        ]
    ]); ?>
</div>
