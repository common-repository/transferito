<?php if (!$data['metRequirements']): ?>

    <?php echo loadTemplate( 'parts/notice', [
        'title'             => 'Important Information',
        'image'             => 'system-requirements',
        'type'              => 'warning',
        'message'           => 'We can\'t start your migration because Transferito requires the PHP zip module or PHP function to be enabled.',
        'additionalTitle'   => 'To use our plugin:',
        'additionalInfo'    => 'You will have enable either exec or the zip module you can check the guide below to see more information.',
        'links'             => [
            [
                'url'           => 'https://www.namecheap.com/support/knowledgebase/article.aspx/9396/2219/how-to-enable-exec',
                'anchorText'    => 'HOW TO ENABLE PHP exec function'
            ],
            [
                'url'           => 'https://www.youtube.com/watch?v=WfgyusFEQp4?rel=0&modestbranding=1',
                'anchorText'    => 'HOW TO ENABLE PHP Zip module'
            ]
        ],
        'extraInfo'         => [
            'content'   => 'If you are unsure how to enable any of these do this please contact your hosting provider.'
        ]
    ]); ?>

<?php endif; ?>


<?php if ($data['metRequirements']): ?>

    <input type="hidden" id="cPanelCheckSecurity" value="<?php echo wp_create_nonce('cpanel_check'); ?>">

    <div class="transferito__three-columns">
        <div class="transferito__column transferito__navigation-column">
            <?php echo loadTemplate( 'parts/migration/navigation', [
                'destinationURL'        => 'active',
                'chooseMethod'          => 'disabled',
                'emptyItem1'            => 'disabled',
                'emptyItem2'            => 'disabled',
                'emptyItem3'            => 'disabled'
            ]); ?>
        </div>
        <div class="transferito__column transferito__main-column">
            <div class="transferito-text__h1">Let's get started</div>
            <div class="transferito__content-container">
                <div class="transferito-destination-url">
                    <div class="transferito-destination-url__title transferito-text__p1--bold">
                        <span class="transferito-input__required">*</span>
                        Enter the Destination URL
                    </div>
                    <div class="transferito-destination-url__content transferito-text__p--regular">
                        Is the website address, where you want to migrate your current website
                    </div>
                    <div class="transferito-destination-url__input">
                        <div class="transferito-input__dropdown-with-text">
                            <select id="domainProtocol" class="transferito-input__dropdown transferito-input__dropdown--no-border transferito-input__dropdown--border-right">
                                <option>http://</option>
                                <option>https://</option>
                            </select>
                            <input
                                id="domain"
                                class="transferito-input__text-box transferito-input__text-box--no-border transferito-input__text-box--full-width"
                                type="text"
                                value="<?php echo isset($data['url']) ? $data['url'] : ''; ?>">
                        </div>
                    </div>
                    <div class="transferito-destination-url__action-button">
                        <button id="cpanelCheck" class="transferito-button transferito-button__primary transferito-button--small transferito__check-cpanel-availability" <?php echo isset($data['url']) ? '' : 'disabled'; ?>>CONTINUE</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="transferito__column transferito__pro-tip-column">
            <?php echo loadTemplate( 'parts/migration/pro-tip', [
                'mainText'  => 'Enter the exact URL or link where you want to migrate (Copy or move) your current website',
                'secondaryText'  => [
                    'text' => 'For example enter your URL like this',
                    'items' => [
                        'www.example.com',
                        'example.com',
                        'test.example.com'
                    ]
                ]
            ]); ?>
        </div>
    </div>

    <div id="errorIncorrectModal" class="transferito__hide-element">
        <?php echo loadTemplate( 'parts/notice', [
            'image'             => 'incorrect-url',
            'type'              => 'error',
            'messageTitle'      => 'Error finding URL!',
            'message'           => 'We can not find the Destination URL that you\'ve entered, please remove the http:// or https:// from your URL and try again.',
            'closeButton'       => true,
            'additionalInfo'    => 'If you are still facing this issue our migration specialists are happy to help you resolve this issue.',
            'supportLink'       => [
                'anchorText'    => 'Create Support Ticket',
                'url'           => 'https://wordpress.org/support/plugin/transferito/'
            ]
        ]); ?>
    </div>

    <div id="firstMigrationInformation" class="transferito__hide-element">
        <?php echo loadTemplate( 'parts/information', [
            'name'              => 'Your First Migration with Transferito',
            'title'             => 'Your First Migration with Transferito',
            'mainContent'       => 'This video will give you step by step walk through on how use Transferito to complete your first migration.',
            'videoID'           => '5saFT85LCo8',
        ]); ?>
    </div>

<?php endif; ?>



