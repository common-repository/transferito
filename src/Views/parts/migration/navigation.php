<div class="transferito-navigation">

    <?php if (isset($data['destinationURL'])): ?>
        <div class="transferito-navigation__item">
            <?php if ($data['destinationURL'] === 'completed'): ?>
                <div class="transferito-navigation__item-connector"></div>
            <?php endif; ?>
            <div class="transferito-navigation__item-icon <?php echo ($data['destinationURL'] === 'disabled') ? 'transferito-navigation__item-icon--disabled' : ''; ?>">
                <div class="transferito-icon <?php echo ($data['destinationURL'] === 'completed') ? 'transferito-icon--completed' : 'transferito-icon--link'; ?>"></div>
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-anchor transferito-text__p1--bold">
                    Destination URL
                </div>
                <?php if ($data['destinationURL'] === 'completed'): ?>
                    <div class="transferito-navigation__item-information">
                        <div class="transferito-navigation__content transferito-text__small">
                            <?php echo get_transient('transferito_migration_domain'); ?>
                        </div>
                        <?php if (isset($data['startMigration']) && $data['startMigration'] !== 'active'): ?>
                        <div data-screen-route="destinationURL" class="transferito-navigation__link transferito__screen-routing transferito-text__small--semi-bold">Update</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['chooseMethod'])): ?>
        <div class="transferito-navigation__item">
            <?php if ($data['chooseMethod'] === 'completed'): ?>
                <div class="transferito-navigation__item-connector"></div>
            <?php endif; ?>
            <div class="transferito-navigation__item-icon <?php echo ($data['chooseMethod'] === 'disabled') ? 'transferito-navigation__item-icon--disabled' : ''; ?>">
                <div class="transferito-icon <?php echo ($data['chooseMethod'] === 'completed') ? 'transferito-icon--completed' : 'transferito-icon--choose'; ?>"></div>
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-anchor transferito-text__p1--bold">
                    Choose Method
                </div>
                <?php if ($data['chooseMethod'] === 'completed'): ?>
                    <div class="transferito-navigation__item-information">
                        <div class="transferito-navigation__content transferito-text__small">
                            <?php echo get_transient('transferito_transfer_method') === 'cpanel' ? 'cPanel' : 'FTP'; ?>
                        </div>
                        <?php if (isset($data['startMigration']) && $data['startMigration'] !== 'active'): ?>
                        <div data-screen-route="migrationMethodSelection" class="transferito-navigation__link transferito__screen-routing transferito-text__small--semi-bold">Update</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['cPanelAuthentication'])): ?>
        <div class="transferito-navigation__item">
            <?php if ($data['cPanelAuthentication'] === 'completed'): ?>
                <div class="transferito-navigation__item-connector"></div>
            <?php endif; ?>
            <div class="transferito-navigation__item-icon <?php echo ($data['cPanelAuthentication'] === 'disabled') ? 'transferito-navigation__item-icon--disabled' : ''; ?>">
                <div class="transferito-icon <?php echo ($data['cPanelAuthentication'] === 'completed') ? 'transferito-icon--completed' : 'transferito-icon--authentication'; ?>"></div>
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-anchor transferito-text__p1--bold">
                    cPanel Authentication
                </div>
                <?php if ($data['cPanelAuthentication'] === 'completed'): ?>
                    <?php
                    /**
                     * cPanel details
                     */
                    $cpanelDetails = get_transient('transferito_cpanel_auth_details');
                    ?>
                    <div class="transferito-navigation__item-information">
                        <div class="transferito-navigation__title transferito-text__small--semi-bold">cPanel Username:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $cpanelDetails['cpanelUser'];?></div>
                        <?php if (!$cpanelDetails['cPanelUseApiToken']) : ?>
                            <div class="transferito-navigation__title transferito-text__small--semi-bold">cPanel Password:</div>
                            <div class="transferito-navigation__content transferito-text__small"><?php echo $cpanelDetails['cpanelPass'];?></div>
                        <?php endif; ?>
                        <?php if ($cpanelDetails['cPanelUseApiToken']) : ?>
                            <div class="transferito-navigation__title transferito-text__small--semi-bold">cPanel API Token:</div>
                            <div class="transferito-navigation__content transferito-text__small"><?php echo $cpanelDetails['cPanelApiToken'];?></div>
                        <?php endif; ?>
                        <?php if (isset($data['startMigration']) && $data['startMigration'] !== 'active'): ?>
                        <div data-screen-route="cpanelAuthentication" class="transferito-navigation__link transferito__screen-routing transferito-text__small--semi-bold">Update</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['selectDomain'])): ?>
        <div class="transferito-navigation__item">
            <?php if ($data['selectDomain'] === 'completed'): ?>
                <div class="transferito-navigation__item-connector"></div>
            <?php endif; ?>
            <div class="transferito-navigation__item-icon <?php echo ($data['selectDomain'] === 'disabled') ? 'transferito-navigation__item-icon--disabled' : ''; ?>">
                <div class="transferito-icon <?php echo ($data['selectDomain'] === 'completed') ? 'transferito-icon--completed' : 'transferito-icon--domain'; ?>"></div>
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-anchor transferito-text__p1--bold">
                    Select Domain
                </div>
                <?php if ($data['selectDomain'] === 'completed'): ?>
                    <div class="transferito-navigation__item-information">
                        <div class="transferito-navigation__content transferito-text__small"><?php echo get_transient('transferito_migration_domain'); ?></div>
                        <?php if (isset($data['startMigration']) && $data['startMigration'] !== 'active'): ?>
                        <div class="transferito-navigation__link transferito-text__small--semi-bold">Update</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['ftpAuthentication'])): ?>
        <div class="transferito-navigation__item">
            <?php if ($data['ftpAuthentication'] === 'completed'): ?>
                <div class="transferito-navigation__item-connector"></div>
            <?php endif; ?>
            <div class="transferito-navigation__item-icon <?php echo ($data['ftpAuthentication'] === 'disabled') ? 'transferito-navigation__item-icon--disabled' : ''; ?>">
                <div class="transferito-icon <?php echo ($data['ftpAuthentication'] === 'completed') ? 'transferito-icon--completed' : 'transferito-icon--authentication'; ?>"></div>
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-anchor transferito-text__p1--bold">
                    FTP Details
                </div>
                <?php if ($data['ftpAuthentication'] === 'completed'): ?>
                    <?php
                        /**
                         * FTP Details
                         */
                        $ftpDetail = get_transient('transferito_manual_server_detail');
                    ?>
                    <div class="transferito-navigation__item-information">

                        <div class="transferito-navigation__title transferito-text__small--semi-bold">Connection Type:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo ($ftpDetail['useSFTP']) == '0' ? 'FTP' : 'SFTP';?></div>
                        <div class="transferito-navigation__title transferito-text__small--semi-bold">FTP Host:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $ftpDetail['ftpHost'];?></div>
                        <div class="transferito-navigation__title transferito-text__small--semi-bold">FTP Username:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $ftpDetail['ftpUser'];?></div>
                        <div class="transferito-navigation__title transferito-text__small--semi-bold">FTP Password:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $ftpDetail['ftpPass'];?></div>
                        <div class="transferito-navigation__title transferito-text__small--semi-bold">FTP Port:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $ftpDetail['ftpPort'];?></div>
                        <?php if (isset($data['startMigration']) && $data['startMigration'] !== 'active'): ?>
                        <div data-screen-route="ftpAuthentication" class="transferito-navigation__link transferito__screen-routing transferito-text__small--semi-bold">Update</div>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['selectDirectory'])): ?>
        <div class="transferito-navigation__item">
            <?php if ($data['selectDirectory'] === 'completed'): ?>
                <div class="transferito-navigation__item-connector"></div>
            <?php endif; ?>
            <div class="transferito-navigation__item-icon <?php echo ($data['selectDirectory'] === 'disabled') ? 'transferito-navigation__item-icon--disabled' : ''; ?>">
                <div class="transferito-icon <?php echo ($data['selectDirectory'] === 'completed') ? 'transferito-icon--completed' : 'transferito-icon--folder'; ?>"></div>
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-anchor transferito-text__p1--bold">
                    Select Directory
                </div>
                <?php if ($data['selectDirectory'] === 'completed'): ?>
                    <div class="transferito-navigation__item-information">
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $ftpDetail['ftpPath'];?></div>
                        <?php if (isset($data['startMigration']) && $data['startMigration'] !== 'active'): ?>
                        <div data-screen-route="directorySelector" class="transferito-navigation__link transferito__screen-routing transferito-text__small--semi-bold">Update</div>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['databaseAuthentication'])): ?>
        <div class="transferito-navigation__item">
            <?php if ($data['databaseAuthentication'] === 'completed'): ?>
                <div class="transferito-navigation__item-connector"></div>
            <?php endif; ?>
            <div class="transferito-navigation__item-icon <?php echo ($data['databaseAuthentication'] === 'disabled') ? 'transferito-navigation__item-icon--disabled' : ''; ?>">
                <div class="transferito-icon <?php echo ($data['databaseAuthentication'] === 'completed') ? 'transferito-icon--completed' : 'transferito-icon--database'; ?>"></div>
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-anchor transferito-text__p1--bold">
                    Database Details
                </div>
                <?php if ($data['databaseAuthentication'] === 'completed'): ?>
                    <?php
                    /**
                     * FTP Details
                     */
                    $ftpDetail = get_transient('transferito_manual_server_detail');
                    ?>
                    <div class="transferito-navigation__item-information">
                        <div class="transferito-navigation__title transferito-text__small--semi-bold">Database Host:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $ftpDetail['dbHost'];?></div>
                        <div class="transferito-navigation__title transferito-text__small--semi-bold">Database Name:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $ftpDetail['dbName'];?></div>
                        <div class="transferito-navigation__title transferito-text__small--semi-bold">Database User:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $ftpDetail['dbUser'];?></div>
                        <div class="transferito-navigation__title transferito-text__small--semi-bold">Database Password:</div>
                        <div class="transferito-navigation__content transferito-text__small"><?php echo $ftpDetail['dbPass'];?></div>
                        <?php if (isset($data['startMigration']) && $data['startMigration'] !== 'active'): ?>
                        <div class="transferito-navigation__link transferito-text__small--semi-bold">Update</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['startMigration'])): ?>
        <div class="transferito-navigation__item">
            <?php if ($data['startMigration'] === 'completed'): ?>
                <div class="transferito-navigation__item-connector"></div>
            <?php endif; ?>
            <div class="transferito-navigation__item-icon <?php echo ($data['startMigration'] === 'disabled') ? 'transferito-navigation__item-icon--disabled' : ''; ?>">
                <div class="transferito-icon <?php echo ($data['startMigration'] === 'completed') ? 'transferito-icon--completed' : 'transferito-icon--reload'; ?>"></div>
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-anchor transferito-text__p1--bold">
                    Start Migration
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['emptyItem1'])) : ?>
        <div class="transferito-navigation__item">
            <div class="transferito-navigation__item-icon transferito-navigation__item-icon--disabled">
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-empty"></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['emptyItem2'])) : ?>
        <div class="transferito-navigation__item">
            <div class="transferito-navigation__item-icon transferito-navigation__item-icon--disabled">
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-empty"></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($data['emptyItem3'])) : ?>
        <div class="transferito-navigation__item">
            <div class="transferito-navigation__item-icon transferito-navigation__item-icon--disabled">
            </div>
            <div class="transferito-navigation__item-details">
                <div class="transferito-navigation__item-empty"></div>
            </div>
        </div>
    <?php endif; ?>
    
</div>
