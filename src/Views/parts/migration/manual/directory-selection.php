<input type="hidden" id="manualMigrationDirectorySelection" value="<?php echo wp_create_nonce('manual_migration_directory_selection'); ?>">
<input type="hidden" id="directoryKey" value="<?php echo wp_create_nonce("get_directory_list"); ?>">

<div class="transferito__three-columns">
    <div class="transferito__column transferito__navigation-column">
        <?php echo loadTemplate( 'parts/migration/navigation', [
            'destinationURL'        => 'completed',
            'chooseMethod'          => 'completed',
            'ftpAuthentication'     => 'completed',
            'selectDirectory'       => 'active',
            'databaseAuthentication'=> 'disabled',
            'startMigration'        => 'disabled'
        ]); ?>
    </div>
    <div class="transferito__column transferito__main-column">
        <div class="transferito-text__h1">Finding your installation directory</div>
        <div class="transferito-directory-selection__content transferito-text__p--regular">
            We will search for the correct directory on your destination server to migrate your WordPress site
        </div>
        <div class="transferito__content-container">
            <div class="transferito-directory-selection">

                <div id="ftpDirectorySelector" class="transferito-directory-selection__selector">
                    <div class="transferito-directory-selection__title--15-bottom-margin transferito-text__p1--bold">
                        Please wait... We're just finding the correct directory
                    </div>

                    <div class="transferito-directory-selection__check">
                        <div class="transferito-directory-selection__check-loader">Checking Directory:</div>
                        <div id="currentFTPPathCheck" class="transferito-directory-selection__check-text">...</div>
                    </div>
                </div>

                <div id="directorySelectionCheckSuccess" class="transferito-directory-selection__selector transferito__hide-element">
                    <div class="transferito-directory-selection__title--15-bottom-margin transferito-text__p1--bold">
                        We've found your WordPress installation directory
                    </div>

                    <div class="transferito-directory-selection__check">
                        <div class="transferito-navigation__item-icon transferito-navigation__item-icon--small">
                            <div class="transferito-icon transferito-icon--completed-small"></div>
                        </div>
                        <div class="transferito-text__p--regular transferito-directory-selection--text-paddingtop-3">Click the continue button to proceed with your migration.</div>
                    </div>
                </div>

                <div id="directorySelectionCheckFailure" class="transferito-directory-selection__selector transferito__hide-element">
                    <div class="transferito-directory-selection__title--15-bottom-margin transferito-text__p1--bold">
                        We've found your WordPress installation directory
                    </div>

                    <div class="transferito-directory-selection__check">
                        <div class="transferito-navigation__item-icon ">
                            <div class="transferito-icon transferito-icon--completed"></div>
                        </div>
                        <div id="currentFTPPathCheckFailure" class="transferito-directory-selection__check-text">This has been completed</div>
                    </div>
                </div>

                <div class="transferito-directory-selection__action-buttons">
                    <button id="routeToFTPAuthentication" data-screen-route="ftpAuthentication" class="transferito-button transferito-button__secondary transferito-button--small transferito__screen-routing">BACK</button>
                    <button id="manualDirectorySelection" class="transferito-button transferito-button__primary transferito-button--small transferito__directory-selection-validation">CONTINUE</button>
                </div>
            </div>
        </div>
    </div>
    <div class="transferito__column transferito__pro-tip-column">
        <?php echo loadTemplate( 'parts/migration/pro-tip', [
            'textBox' => [
                "content" => "The directory selector automatically navigates through your servers directories to find your WordPress installation directory."
            ],
        ]); ?>
    </div>
</div>





