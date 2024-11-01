<input type="hidden" id="selectMigrationMethod" value="<?php echo wp_create_nonce('select_migration_method'); ?>">

<div class="transferito__three-columns">
        <div class="transferito__column transferito__navigation-column">
            <?php echo loadTemplate( 'parts/migration/navigation', [
                'destinationURL'        => 'completed',
                'chooseMethod'          => 'active',
                'emptyItem1'            => 'disabled',
                'emptyItem2'            => 'disabled',
                'emptyItem3'            => 'disabled'
            ]); ?>
        </div>
        <div class="transferito__column transferito__main-column">
            <div class="transferito-text__h1">Select Migration Method</div>
            <div class="transferito__content-container">
                
                <div class="transferito-migration-method">
                    
                    <div class="transferito-migration-method__selection-boxes">
                        <div id="selectMethodCpanel" data-select-migration-method="cpanel" class="transferito-migration-method__selection-method <?php echo ($data['cpanelAllowed']) ? 'transferito-migration-method__selection-method--selected' : ''; ?>">
                            <?php if ($data['cpanelAllowed']) : ?>
                                <div class="transferito-migration-method__recommended">RECOMMENDED</div>
                            <?php endif; ?>
                            <div class="transferito-migration-method__icon transferito-migration-method__icon--cpanel"></div>
                            <div class="transferito-migration-method__title transferito-text__p1--bold">Migrate using cPanel</div>
                            <?php if ($data['cpanelAllowed']) : ?>
                                <div class="transferito-migration-method__pill-holder">
                                    <div class="transferito-migration-method__pill transferito-migration-method__pill--dark-purple">EASIER</div>
                                    <div class="transferito-migration-method__pill transferito-migration-method__pill--light-purple">FASTER</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="selectMethodFTP" data-select-migration-method="manual" class="transferito-migration-method__selection-method <?php echo (!$data['cpanelAllowed']) ? 'transferito-migration-method__selection-method--selected' : ''; ?>">
                            <?php if (!$data['cpanelAllowed']) : ?>
                                <div class="transferito-migration-method__recommended">RECOMMENDED</div>
                            <?php endif; ?>
                            <div class="transferito-migration-method__icon transferito-migration-method__icon--ftp"></div>
                            <div class="transferito-migration-method__title transferito-text__p1--bold">Migrate using FTP</div>
                            <?php if ($data['cpanelAllowed']) : ?>
                                <div class="transferito-migration-method__pill-holder">
                                    <div class="transferito-migration-method__pill transferito-migration-method__pill--blue">MANUAL</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="transferito-migration-method__action-buttons">
                        <button id="routeToDestinationURL" data-screen-route="destinationURL" class="transferito-button transferito-button__secondary transferito-button--small transferito__screen-routing">BACK</button>
                        <button id="selectMigrationMethod" class="transferito-button transferito-button__primary transferito-button--small transferito__select-migration-method">CONTINUE</button>
                    </div>

                </div>

            </div>
        </div>
        <div id="cPanelProTip" class="transferito__column transferito__pro-tip-column <?php echo (!$data['cpanelAllowed']) ? 'transferito__pro-tip-column--hide' : '' ?>">
            <?php echo loadTemplate( 'parts/migration/pro-tip', [
                'mainText'  => 'To use cPanel as your migration method. You will need to have access to your hosting dashboard.',
                'secondaryText'  => [
                    'text' => 'You will need the following',
                    'items' => [
                        'cPanel Username',
                        'cPanel API Token',
                    ]
                ],
                'textBox' => [
                    "content" => "
                        Our migrations with cPanel are faster and easier because you do not need to create FTP details or database details.
                        <br><br>Not sure how to get your cPanel details?",
                    "link" => [
                        "anchorText" => "Click here, to watch a video on how to get your cPanel details",
                        "modalName" => "getCpanelDetails"
                    ]
                ]
            ]); ?>
        </div>
        <div id="FTPProTip" class="transferito__column transferito__pro-tip-column <?php echo ($data['cpanelAllowed']) ? 'transferito__pro-tip-column--hide' : ''?>">
            <?php echo loadTemplate( 'parts/migration/pro-tip', [
                'secondaryText'  => [
                    'text' => 'To use FTP as your migration method, you will need the following',
                    'items' => [
                        'FTP/SFTP Details for your destination server/website',
                        'Database Details for your destination server/website'
                    ]
                ],
                'textBox' => [
                    "content" => "Need help with finding your FTP details?",
                    "link" => [
                        "anchorText" => "Click here, to watch a video on how to get your FTP details",
                        "modalName" => "getFTPDetails"
                    ]
                ]
            ]); ?>
        </div>
    </div>

<div id="getCpanelDetails" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Get your cPanel details',
        'title'             => 'Get your cPanel details',
        'mainContent'       => 'This video tutorial will give you an overview on how to get your cPanel details from your hosting provider.',
        'videoID'           => 'y0atIDHUPrE',
    ]); ?>
</div>
<div id="getFTPDetails" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Get your FTP Details',
        'title'             => 'Get your FTP Details',
        'mainContent'       => 'This video tutorial will give you an overview on how to get your FTP details from your hosting provider.',
        'videoID'           => 'FwZ9DWjm5to',
        'subTitle'          => 'Use the dropdown below to select the video for your hosting provider:',
        'guideMissingLink'  => true,
        'steps'             => [
            'options'   => [
                [
                    'value' => 'bluehost',
                    'text'  => 'Bluehost',
                    'videoID' => 'M4j16LqsV28'
                ],
                [
                    'value' => 'siteground',
                    'text'  => 'Siteground',
                    'videoID' => 'wehSB52cTyw'
                ],
                [
                    'value' => 'namehero',
                    'text'  => 'Namehero',
                    'videoID' => 'HhhvYwGdyoE'
                ],
                [
                    'value' => 'go-daddy',
                    'text'  => 'Go Daddy',
                    'videoID' => 'c6U8Ndsm340'
                ],
                [
                    'value' => 'ionos',
                    'text'  => 'IONOS',
                    'videoID' => 'NN3fw3tDT0Y'
                ],
                [
                    'value' => 'hostmonster',
                    'text'  => 'Hostmonster',
                    'videoID' => 'qkeimaL8BkU'
                ],
            ],
        ]
    ]); ?>
</div>


