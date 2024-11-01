<input type="hidden" id="cPanelMigration" value="<?php echo wp_create_nonce('cpanel_migration'); ?>">

<div class="transferito__three-columns">
    <div class="transferito__column transferito__navigation-column">
        <?php echo loadTemplate( 'parts/migration/navigation', [
            'destinationURL'        => 'completed',
            'chooseMethod'          => 'completed',
            'cPanelAuthentication'  => 'active',
            'selectDomain'          => 'disabled',
            'startMigration'        => 'disabled',
        ]); ?>
    </div>
    <div class="transferito__column transferito__main-column">
        <div class="transferito-text__h1">Enter your cPanel login details</div>

        <div class="transferito__content-container">
            <div class="transferito-cpanel-authentication">
                <div class="transferito-cpanel-authentication__title transferito-text__p1--bold">
                    <span class="transferito-input__required">*</span>
                    Enter your cPanel Username
                </div>
                <div class="transferito-cpanel-authentication__input">
                    <input
                        id="field__cPanelUser"
                        name="cpanelUser"
                        value="<?php echo isset($data['cpanelDetail']['cpanelUser']) ? $data['cpanelDetail']['cpanelUser'] : ''; ?>"
                        class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin" type="text">
                </div>
                <div id="cpanelAPITokenElement" class="<?php echo isset($data['cpanelDetail']['cPanelUseApiToken']) && !$data['cpanelDetail']['cPanelUseApiToken'] ? 'transferito__hide-element' : ''; ?>">
                    <div class="transferito-cpanel-authentication__title transferito-text__p1--bold">
                        <span class="transferito-input__required">*</span>
                        Enter your cPanel API Token
                    </div>
                    <div class="transferito-cpanel-authentication__input">
                        <input name="cPanelApiToken"
                               id="field__cPanelApiToken"
                               value="<?php echo isset($data['cpanelDetail']['cPanelApiToken']) ? $data['cpanelDetail']['cPanelApiToken'] : ''; ?>"
                               class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin" type="text">
                    </div>
                </div>
                
                <div id="cpanelPasswordElement" class="<?php echo (isset($data['cpanelDetail']['cPanelUseApiToken']) && $data['cpanelDetail']['cPanelUseApiToken']) || !$data['cpanelDetail'] ? 'transferito__hide-element' : ''; ?>">
                    <div class="transferito-cpanel-authentication__title transferito-text__p1--bold">
                        <span class="transferito-input__required">*</span>
                        Enter your cPanel Password
                    </div>
                    <div class="transferito-cpanel-authentication__input">
                        <input name="cpanelPass"
                               id="field__cPanelPass"
                               value="<?php echo isset($data['cpanelDetail']['cpanelPass']) ? $data['cpanelDetail']['cpanelPass'] : ''; ?>"
                               class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin" type="text">
                    </div>
                </div>
                <div class="transferito-cpanel-authentication__action-buttons">
                    <button id="routeToMigrationMethodSelection" data-screen-route="migrationMethodSelection" class="transferito-button transferito-button__secondary transferito-button--small transferito__screen-routing">BACK</button>
                    <button id="cpanelAuth" class="transferito-button transferito-button__primary transferito-button--small transferito__cpanel-authentication" <?php echo $data['cpanelCompleted'] ? '' : 'disabled'?>>CONTINUE</button>
                </div>
            </div>
        </div>
    </div>
    <div class="transferito__column transferito__pro-tip-column">
        <?php echo loadTemplate( 'parts/migration/pro-tip', [
            'textBox' => [
                "content" => "Our migrations with cPanel are faster and easier because you do not need to create FTP or database details.",
            ],
            'faqs' => [
                [
                    "anchorText"    => "What is cPanel?",
                    "modalName"     => "whatIsCpanel"
                ],
                [
                    "anchorText"    => "Where can I find my cPanel Username?",
                    "modalName"     => "findCpanelUsername"
                ],
                [
                    "anchorText"    => "Where can I find my cPanel API Token?",
                    "modalName"     => "findCpanelAPIToken"
                ],
                [
                    "anchorText"    => "How can I create my cPanel API Token?",
                    "modalName"     => "createCpanelAPIToken"
                ],
                [
                    "anchorText"    => "Where can I find my cPanel Password?",
                    "modalName"     => "findCpanelPassword"
                ],
            ]
        ]); ?>
    </div>
</div>

<div id="errorFailedCpanelAuth" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/notice', [
        'image'             => 'failed-auth',
        'type'              => 'error',
        'messageTitle'      => 'cPanel connection failed!',
        'message'           => 'We were unable to connect to your server using your cPanel login details. Please check that your details are correct and try again.',
        'closeButton'       => true,
        'additionalInfo'    => 'If you are still facing this issue our migration specialists are happy to help you resolve this issue.',
        'supportLink'       => [
            'anchorText'    => 'Create Support Ticket',
            'url'           => 'https://wordpress.org/support/plugin/transferito/'
        ]
    ]); ?>
</div>

<div id="whatIsCpanel" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'What is cPanel?',
        'title'             => 'What is cPanel?',
        'mainContent'       => 'This video tutorial will give you a quick introduction into what cPanel is and why hosting providers use cPanel.',
        'videoID'           => '4XpV0-DQuJ4',
    ]); ?>
</div>
<div id="findCpanelUsername" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Find your cPanel username',
        'title'             => 'Find your cPanel username',
        'mainContent'       => 'This video will show you where to find your cPanel username in your cPanel dashboard.',
        'videoID'           => '0KVm2NAHBck',
        'additionalInfo'    => [
            'text'  => 'Now you\'ve learnt how to find your cPanel username, the next step is to create a cPanel API token.',
            'links' => [
                [
                    'modalName' => 'createCpanelAPIToken',
                    'text'      => 'How can I create my cPanel API Token?'
                ]
            ]
        ]
    ]); ?>
</div>
<div id="findCpanelAPIToken" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find my cPanel API Token?',
        'title'             => 'Where can I find my cPanel API Token?',
        'mainContent'       => '
Unfortunately, cPanel API Tokens are not able to be viewed again. <br><br>
So if you have previously created a cPanel API Token and not saved it in a secure location or you haven\'t created an API Token. You will have to create one.',
        'additionalInfo'    => [
            'text'  => '
<strong>Note:</strong><br> 
Click on the link below to follow a step-by-step guide on how to create your cPanel API token',
            'links' => [
                [
                    'modalName' => 'createCpanelAPIToken',
                    'text'      => 'How can I create my cPanel API Token?'
                ],
            ]
        ]
    ]); ?>
</div>
<div id="createCpanelAPIToken" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'How can I create my cPanel API Token',
        'title'             => 'How can I create my cPanel API Token',
        'mainContent'       => 'This video will give you a step by step walkthrough on how to create your cPanel API token for your hosting account.',
        'videoID'           => 'Fde7hGGM-yQ'
    ]); ?>
</div>
<div id="findCpanelPassword" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/information', [
        'name'              => 'Where can I find my cPanel password?',
        'title'             => 'Where can I find my cPanel password?',
        'mainContent'       => '
        The best place to start is by checking your email, your web hosting provider should have sent you an email with your cPanel login details.
        ',
        'additionalInfo'    => [
            'text'  => '
<strong>Please Note:</strong><br> 
Some web hosting providers may not provide you with your cPanel password. If this is the case please use a cPanel API token.<br><br>
Click on the link below to follow a step-by-step guide on how to create your cPanel API token',
            'links' => [
                [
                    'modalName' => 'createCpanelAPIToken',
                    'text'      => 'How can I create my cPanel API Token?'
                ],
            ]
        ]
    ]); ?>
</div>
