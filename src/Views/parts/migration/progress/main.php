<div id="progressMainContainer" class="transferito__three-columns">
    <div class="transferito__column transferito__navigation-column">
        <?php
            $cpanelOptions = [
                'destinationURL'        => 'completed',
                'chooseMethod'          => 'completed',
                'cPanelAuthentication'  => 'completed',
                'selectDomain'          => 'completed',
                'startMigration'        => 'active',
            ];
            $ftpOptions = [
                'destinationURL'        => 'completed',
                'chooseMethod'          => 'completed',
                'ftpAuthentication'     => 'completed',
                'selectDirectory'       => 'completed',
                'databaseAuthentication'=> 'completed',
                'startMigration'        => 'active'
            ];

            $selectedNavOptions = $data['method'] === 'ftp' ? $ftpOptions : $cpanelOptions;
            echo loadTemplate( 'parts/migration/navigation', $selectedNavOptions);
        ?>
    </div>
    <div class="transferito__column transferito__main-column">
        <div id="migrationProgressTitle" class="transferito-text__h1">Migration in progress</div>
        <div class="transferito__content-container transferito__content-container--no-padding">

            <div class="transferito-migration-progress">

                <div class="transferito-migration-progress__overview">
                    <div class="transferito-migration-progress__bar">
                        <div id="overviewProgressPercentageBar" class="transferito-migration-progress__bar--value"></div>
                    </div>
                    <div class="transferito-migration-progress__amount">
                        <div class="transferito-text__h2--bold"><span id="progressOverviewPercentage">0</span>%</div>
                    </div>
                </div>

                <div id="progress__prepareBackup" class="transferito-migration-progress__step transferito-migration-progress__disabled-text">
                    <div class="transferito-migration-progress__step-icon transferito__hide-element">
                        <div class="transferito-navigation__item-icon">
                            <div class="transferito-icon transferito-icon--completed"></div>
                        </div>
                    </div>
                    <div class="transferito-migration-progress__step-percent">
                        <div class="transferito-text__p"><span>0</span>%</div>
                    </div>
                    <div class="transferito-migration-progress__step-title transferito-text__p">
                        Preparing your backup
                        <div class="transferito-migration-progress__error-container transferito__hide-element"></div>
                    </div>
                </div>

                <div id="progress__backupInstallation" class="transferito-migration-progress__step transferito-migration-progress__disabled-text">
                    <div class="transferito-migration-progress__step-icon transferito__hide-element">
                        <div class="transferito-navigation__item-icon">
                            <div class="transferito-icon transferito-icon--completed"></div>
                        </div>
                    </div>
                    <div class="transferito-migration-progress__step-percent">
                        <div class="transferito-text__p"><span id="backupInstallationProgressPercentage">0</span>%</div>
                    </div>
                    <div class="transferito-migration-progress__step-title transferito-text__p">
                        Backing up your WordPress installation
                        <div class="transferito-migration-progress__error-container transferito__hide-element"></div>
                    </div>
                </div>

                <?php if ($data['uploadBackup']) : ?>
                    <div id="progress__uploadBackup" class="transferito-migration-progress__step transferito-migration-progress__disabled-text">
                        <div class="transferito-migration-progress__step-icon transferito__hide-element">
                            <div class="transferito-navigation__item-icon">
                                <div class="transferito-icon transferito-icon--completed"></div>
                            </div>
                        </div>
                        <div class="transferito-migration-progress__step-percent">
                            <div class="transferito-text__p"><span id="progressPercentage">0</span>%</div>
                        </div>
                        <div class="transferito-migration-progress__step-title transferito-text__p">
                            Uploading your WordPress backup
                            <div class="transferito-migration-progress__error-container transferito__hide-element"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="progress__downloadingBackup" class="transferito-migration-progress__step transferito-migration-progress__disabled-text">
                    <div class="transferito-migration-progress__step-icon transferito__hide-element">
                        <div class="transferito-navigation__item-icon">
                            <div class="transferito-icon transferito-icon--completed"></div>
                        </div>
                    </div>
                    <div class="transferito-migration-progress__step-percent">
                        <div class="transferito-text__p"><span id="downloadBackupProgressPercentage">0</span>%</div>
                    </div>

                    <div class="transferito-migration-progress__step-title transferito-text__p">
                        Downloading your backup
                        <div class="transferito-migration-progress__error-container transferito__hide-element"></div>
                    </div>
                </div>

                <div id="progress__extractingBackup" class="transferito-migration-progress__step transferito-migration-progress__disabled-text">
                    <div class="transferito-migration-progress__step-icon transferito__hide-element">
                        <div class="transferito-navigation__item-icon">
                            <div class="transferito-icon transferito-icon--completed"></div>
                        </div>
                    </div>
                    <div class="transferito-migration-progress__step-percent">
                        <div class="transferito-text__p"><span id="extractingBackupProgressPercentage">0</span>%</div>
                    </div>

                    <div class="transferito-migration-progress__step-title transferito-text__p">
                        Extracting your WordPress backup
                        <div class="transferito-migration-progress__error-container transferito__hide-element"></div>
                    </div>
                </div>

                <?php if ($data['installDatabase']) : ?>
                    <div id="progress__installingWordPress" class="transferito-migration-progress__step transferito-migration-progress__disabled-text">
                        <div class="transferito-migration-progress__step-icon transferito__hide-element">
                            <div class="transferito-navigation__item-icon">
                                <div class="transferito-icon transferito-icon--completed"></div>
                            </div>
                        </div>
                        <div class="transferito-migration-progress__step-percent">
                            <div class="transferito-text__p"><span id="installDatabaseProgressPercentage">0</span>%</div>
                        </div>

                        <div class="transferito-migration-progress__step-title transferito-text__p">
                            Installing your database
                            <div class="transferito-migration-progress__error-container transferito__hide-element"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="progress__finalizingWPInstall" class="transferito-migration-progress__step transferito-migration-progress__disabled-text">
                    <div class="transferito-migration-progress__step-icon transferito__hide-element">
                        <div class="transferito-navigation__item-icon">
                            <div class="transferito-icon transferito-icon--completed"></div>
                        </div>
                    </div>
                    <div class="transferito-migration-progress__step-percent">
                        <div class="transferito-migration-progress__final-step transferito-migration-progress__final-step--static"></div>
                    </div>

                    <div class="transferito-migration-progress__step-title transferito-text__p">
                        Finalizing your WordPress setup
                        <div class="transferito-migration-progress__error-container transferito__hide-element"></div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <div class="transferito__column transferito__pro-tip-column transferito__pro-tip-column--empty"></div>
</div>


