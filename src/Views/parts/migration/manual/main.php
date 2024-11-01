<input type="hidden" id="ftpMigration" value="<?php echo wp_create_nonce('ftp_migration'); ?>">
<input type="hidden" id="manualMigrationServerDetail" value="<?php echo wp_create_nonce('manual_migration_server_detail'); ?>">

<div class="transferito__three-columns">

    <div class="transferito__column transferito__navigation-column">
        <?php echo loadTemplate( 'parts/migration/navigation', [
            'destinationURL'        => 'completed',
            'chooseMethod'          => 'completed',
            'ftpAuthentication'     => 'active',
            'selectDirectory'       => 'disabled',
            'databaseAuthentication'=> 'disabled',
            'startMigration'        => 'disabled'
        ]); ?>
    </div>
    <div class="transferito__column transferito__main-column">
        <div class="transferito-text__h1">
            Destination Server FTP details!
        </div>
        <div class="transferito__content-container">
            <div class="transferito-ftp-authentication">


                <div class="transferito-ftp-authentication__checkbox">
                    <label class="transferito-input__checkbox--label transferito-text__p1--bold" for="toggleSelectedFolders">
                        <input type="checkbox"
                               class="transferito-input__checkbox transferito-form-element show-selected-folder"
                               name="transfer_selected_folders"
                               id="useSelectedFolders"
                            <?php echo isset($data['detail']['transfer_selected_folders']) && ($data['detail']['transfer_selected_folders'] === 'true') ? 'checked' : ''; ?>>
                        Select the folders to transfer to your destination server
                    </label>
                    <div class="transferito-ftp-authentication__checkbox--content transferito-text__small">If you're transferring your entire website, leave this unchecked!</div>
                </div>

                <div id="selectedFoldersDetails" class="<?php echo isset($data['detail']['transfer_selected_folders']) && ($data['detail']['transfer_selected_folders'] === 'true') ? '' : 'transferito-ftp-authentication__directories'; ?>">
                    <div class="transferito-ftp-authentication__folder-selection">
                        <ul class="transferito-ftp-authentication__folder-list">
                            <?php if (isset($data['directories'])) : ?>
                                <?php foreach ($data['directories'] as $directory => $subDirectories) : ?>
                                    <li>
                                        <label class="menu-item-title">
                                            <input
                                                <?php echo isset($data['detail']['folder_path']) && (in_array(WP_CONTENT_DIR . '/' . $directory, $data['detail']['folder_path'])) ? 'checked' : ''; ?>
                                                    type="checkbox"
                                                    class="transferito-form-element menu-item-checkbox"
                                                    name="folder_path[]"
                                                    value="<?php echo WP_CONTENT_DIR . '/' . $directory; ?>">
                                            <?php echo ucfirst($directory); ?>
                                        </label>

                                        <?php if (count($subDirectories) > 0) : ?>
                                            <span class="transferito-ftp-authentication__folder-expander">+</span>

                                            <?php
                                            if (isset($data['detail']['folder_path'])) {
                                                $cleanPath = preg_quote(WP_CONTENT_DIR . '/', '/');
                                                $folderPaths = preg_replace("/$cleanPath/", "", $data['detail']['folder_path']);
                                                $cleanDirectory = preg_quote($directory . '/', '/');
                                                $foundSubDirectories = preg_grep("/$cleanDirectory/", $folderPaths);
                                                $showSubDirectories = count($foundSubDirectories) > 0;
                                            } else {
                                                $showSubDirectories = false;
                                            }

                                            ?>

                                            <ul class="transferito-ftp-authentication__sub-folders" <?php
                                            echo isset($data['detail']['folder_path']) && $showSubDirectories
                                                ? 'style="display:block;"'
                                                : ''; ?>>
                                                <?php foreach ($subDirectories as $subDirectory) : ?>
                                                    <li>
                                                        <label class="menu-item-title">
                                                            <input
                                                                <?php echo isset($data['detail']['folder_path']) && (in_array(WP_CONTENT_DIR . '/' . $directory . '/' . $subDirectory, $data['detail']['folder_path'])) ? 'checked' : ''; ?>
                                                                    type="checkbox"
                                                                    class="transferito-form-element menu-item-checkbox"
                                                                    name="folder_path[]"
                                                                    value="<?php echo WP_CONTENT_DIR . '/' . $directory . '/' . $subDirectory; ?>">
                                                            <?php echo $subDirectory; ?>
                                                        </label>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>




                <div class="transferito-ftp-authentication__title transferito-text__p1--bold">
                    <span class="transferito-input__required">*</span>
                    Enter your FTP / SFTP Host or FTP / SFTP Server
                </div>
                <div class="transferito-ftp-authentication__input">
                    <input type="text"
                           name="ftpHost"
                           id="field__serverDetailFTPHost"
                           class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin"
                           value="<?php echo isset($data['detail']['ftpHost']) ? $data['detail']['ftpHost'] : ''; ?>">
                </div>

                <div class="transferito-ftp-authentication__title transferito-text__p1--bold">
                    <span class="transferito-input__required">*</span>
                    Enter your FTP / SFTP Username
                </div>
                <div class="transferito-ftp-authentication__input">
                    <input type="text"
                           name="ftpUser"
                           id="field__serverDetailFTPUsername"
                           class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin"
                           value="<?php echo isset($data['detail']['ftpUser']) ? $data['detail']['ftpUser'] : ''; ?>">
                </div>

                <div class="transferito-ftp-authentication__title transferito-text__p1--bold">
                    <span class="transferito-input__required">*</span>
                    Enter your FTP / SFTP Password
                </div>
                <div class="transferito-ftp-authentication__input">
                    <input type="text"
                           name="ftpPass"
                           id="field__serverDetailFTPPass"
                           class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin"
                           value="<?php echo isset($data['detail']['ftpUser']) ? $data['detail']['ftpPass'] : ''; ?>">
                </div>

                <div class="transferito-ftp-authentication__action-buttons">
                    <?php
                        $showButtonDisabled = (isset($data['detail']) && $data['detail'] !== false) ? count($data['detail']) !== 0 : false;
                    ?>
                    <button id="routeToMigrationMethodSelection" data-screen-route="migrationMethodSelection" class="transferito-button transferito-button__secondary transferito-button--small transferito__screen-routing">BACK</button>
                    <button id="manualServerDetails" class="transferito-button transferito-button__primary transferito-button--small transferito__manual-server-details" <?php echo $showButtonDisabled ? '' : 'disabled'; ?>>CONTINUE</button>
                </div>
            </div>
        </div>
    </div>
    <div class="transferito__column transferito__pro-tip-column">
        <?php echo loadTemplate( 'parts/migration/pro-tip', [
            'mainText'  => 'Enter the exact URL or link where you want to migrate (Copy or move) your current website',
            'textBox' => [
                "content" => "New to Transferito? Why not watch a video on how to get started with your first migration.",
                "link" => [
                    "anchorText" => "Click here to see how to create your first migration",
                    "modalName" => "firstFTPMigrationIntro"
                ]
            ],
            'faqs' => [
                [
                    "anchorText"    => "What is FTP & SFTP?",
                    "modalName"     => "whatIsFTP"
                ],
                [
                    "anchorText"    => "How can I create FTP / SFTP details on my destination server?",
                    "modalName"     => "createFTP"
                ],
                [
                    "anchorText"    => "Where can I find FTP / SFTP Port?",
                    "modalName"     => "findFTPPort"
                ],
                [
                    "anchorText"    => "Where can I find FTP / SFTP host?",
                    "modalName"     => "findFTPHost"
                ],
                [
                    "anchorText"    => "Where can I find FTP / SFTP username?",
                    "modalName"     => "findFTPUsername"
                ],
                [
                    "anchorText"    => "What if I don’t know my FTP / SFTP password?",
                    "modalName"     => "findFTPPassword"
                ],
            ]
        ]); ?>
    </div>
</div>

<div id="errorFailedDirectorySearch" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/notice', [
        'image'             => 'directory-failure',
        'type'              => 'error',
        'messageTitle'      => 'We can\'t start your directory check!',
        'message'           => 'This could be an issue on our side, please double check your Destination Server FTP details are correct. If they are, contact our support team and they will be able to help you with this issue.',
        'closeButton'       => true,
        'additionalInfo'    => 'If this issue continues, please contact support and our migration specialists will be more than happy to help you resolve this issue.',
        'supportLink'       => [
            'anchorText'    => 'Create Support Ticket',
            'url'           => 'https://wordpress.org/support/plugin/transferito/'
        ]
    ]); ?>
</div>

<div id="errorDirectoryNotFound" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/notice', [
        'image'             => 'directory-failure',
        'type'              => 'error',
        'messageTitle'      => 'We can\'t find your directory',
        'message'           => 'Please check that you have correctly entered the FTP Details for your Destination Server.',
        'closeButton'       => true,
        'additionalInfo'    => 'If you are still facing this issue our migration specialists are happy to help you resolve this issue.',
        'supportLink'       => [
            'anchorText'    => 'Create Support Ticket',
            'url'           => 'https://wordpress.org/support/plugin/transferito/'
        ]
    ]); ?>
</div>

<div id="errorDirectoryUpdateFailed" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/notice', [
        'image'             => 'directory-failure',
        'type'              => 'error',
        'messageTitle'      => 'Error checking your directory',
        'message'           => 'We are unable to get an update on the status of your directory check. Please retry.',
        'closeButton'       => true,
        'additionalInfo'    => 'If you are still facing this issue our migration specialists are happy to help you resolve this issue.',
        'supportLink'       => [
            'anchorText'    => 'Create Support Ticket',
            'url'           => 'https://wordpress.org/support/plugin/transferito/'
        ]
    ]); ?>
</div>

<div id="errorFailedFTPAuth" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/notice', [
        'image'             => 'failed-auth',
        'type'              => 'error',
        'messageTitle'      => 'FTP connection failed!',
        'message'           => 'There has been an error connecting to your FTP server. Please check your FTP details and try again.',
        'closeButton'       => true,
        'additionalInfo'    => 'If you are still facing this issue our migration specialists are happy to help you resolve this issue.',
        'supportLink'       => [
            'anchorText'    => 'Create Support Ticket',
            'url'           => 'https://wordpress.org/support/plugin/transferito/'
        ]
    ]); ?>
</div>

<div id="firstFTPMigrationIntro" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Your First Migration with Transferito',
        'title'             => 'Your First Migration with Transferito',
        'mainContent'       => 'This video will walk you through a full migration, showing you how to get your all of your migration details and start a full migration.',
        'videoID'           => '5saFT85LCo8'
    ]); ?>
</div>
<div id="whatIsFTP" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'What is FTP?',
        'title'             => 'What is FTP?',
        'mainContent'       => 'This video is a quick introduction that explains what FTP is and how it is used.',
        'videoID'           => 'plD6Mtz4SDM',
    ]); ?>
</div>
<div id="createFTP" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'How can I create my FTP Details?',
        'title'             => 'How can I create my FTP Details?',
        'mainContent'       => 'These videos will show you how to create your FTP details on your destination server.',
        'videoID'           => 'qo6UnKbiGPE',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => 'qTkNGYQJJPs'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => '9YRYbSD-XTU'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => 'UTO2QVpF6GQ'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'jqsFx7RX29o'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => 'qNn0pxkUDBo'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => 'anaROUC6_QA'
                ],
            ],
        ]
    ]); ?>
</div>
<div id="serverConnectionType" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'What is my server connection type?',
        'title'             => 'What is my server connection type?',
        'mainContent'       => 'This is a quick explanation on what the server connection type is, when you need to change it and what it represents.',
        'videoID'           => 'vrwizNoJYh0',
    ]); ?>
</div>
<div id="findFTPPort" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find FTP/SFTP port?',
        'title'             => 'Where can I find FTP/SFTP port?',
        'mainContent'       => 'A quick video showing you how to find your FTP Port for your destination servers hosting provider.',
        'videoID'           => 'nKRxVs9RYKU',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => 'B6C8V-Z8z9s'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'QU16a3vJymw'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => 'q1azRMTbDQ8'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'pTDWtfHa69k'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => 'Dwjd5HeU7_U'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => 'KcRp3XNnxaQ'
                ],
            ],
        ]
    ]); ?>
</div>
<div id="findFTPHost" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find FTP/SFTP host?',
        'title'             => 'Where can I find FTP/SFTP host?',
        'mainContent'       => 'A quick video showing you how to find your FTP Host for your destination servers hosting provider.',
        'videoID'           => '2qV9VB1zVlc',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => 'rHeT5knukOk'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'riABilfHcwE'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => 'bFdBLtlXMPE'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'Vn0_n4fFjdw'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => '6KliToUskeY'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => 'n0RnBPi2Xh8'
                ],
            ],
        ]
    ]); ?>
</div>
<div id="findFTPUsername" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find FTP/SFTP username?',
        'title'             => 'Where can I find FTP/SFTP username?',
        'mainContent'       => 'A quick video showing you how to find your FTP Username for your destination servers hosting provider.',
        'videoID'           => 'lvsd4zlF0-o',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => '59OyAfe787I'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'a1DEp37Lvc0'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => 'SdhocGMwl_4'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'AfGZ5Oc1Juc'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => '7mN1ZDOH5QE'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => '2W7-qmr18tQ'
                ],
            ],
        ]
    ]); ?>
</div>
<div id="findFTPPassword" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find FTP/SFTP password?',
        'title'             => 'Where can I find FTP/SFTP password?',
        'mainContent'       => 'A quick video showing you how to find your FTP Password for your destination servers hosting provider.',
        'videoID'           => 'WNTxoQkuQ8A',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => 'sgb9BX57YGA'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'XYXBw47Mmhw'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => 'iZU2bjt01Pk'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => '6qoRTnGG4L8'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => 'NhjcnnIkDlo'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => 'XI9IVShQXG0'
                ],
            ],
        ]
    ]); ?>
</div>
