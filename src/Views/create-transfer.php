<?php
    /**
     * Get transients to check to see if the user is a premium user
     */
    $emptyKeys = filter_var(get_transient('transferito_empty_keys'), FILTER_VALIDATE_BOOLEAN);
    $availableTransfers = filter_var(get_transient('transferito_has_available_transfers'), FILTER_VALIDATE_BOOLEAN);
?>

<div class="wrap">

    <div class="transferito-header">
        <div class="transferito-header__logo"></div>
        <div class="transferito-header__actions">

            <div class="transferito-header__action-button">
                <a href="https://wordpress.org/support/plugin/transferito/" class="transferito-button transferito-button__support transferito-button--medium transferito-log-event" data-event-name="supportLink" target="_blank">
                    Support
                </a>
            </div>

            <div class="transferito-header__action-button">
                <a href="https://transferito.com/help/intro" class="transferito-button transferito-button__primary--blue transferito-button--small transferito-log-event" data-event-name="viewQuickStartGuide" target="_blank">QUICKSTART GUIDE</a>
            </div>

            <?php if ($emptyKeys || !$availableTransfers) : ?>
                <div class="transferito-header__action-button">
                    <a href="https://transferito.com/upgrade-to-premium" class="transferito-button transferito-button__primary transferito-button--small transferito-log-event" data-event-name="upgradeToPremium_Legend" target="_blank">UPGRADE TO PREMIUM</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="transferitoHeaderLegend" class="transferito-legend transferito__hide-element"></div>

    <input type="hidden" id="nonce" value="<?php echo wp_create_nonce("template_change"); ?>">

    <div id="transferitoTemplate">
        <?php if ($data['userWithoutAPIKeys']) : ?>
            <?php echo loadTemplate( 'parts/loading', [
                'mainMessage'       => 'We\'re just checking the size of your site',
                'secondaryMessage'  => $data['name'] . ', bear with us this shouldn\'t take too long'
            ]); ?>
        <?php endif; ?>

        <?php if (!$data['userWithoutAPIKeys']) : ?>
	        <?php echo loadTemplate( 'parts/loading', [
		        'mainMessage'       => 'We\'re just configuring the plugin for your site',
		        'secondaryMessage'  => $data['name'] . ', bear with us this shouldn\'t take too long'
	        ]); ?>
        <?php endif; ?>
    </div>

    <div id="transferitoModalTemplate" class="transferito-modal transferito__hide-element"></div>

    <div id="requestHostingGuideForm" class="transferito__hide-element">
        <?php echo loadTemplate( 'parts/information', [
            'title'             => 'Request a Hosting Guide',
            'mainContent'       => 'We apologize for not having a guide for your hosting provider. However, we would be more than happy to create one for you. Just enter your details below. Thank you!',
            'guideRequest'      => true
        ]); ?>
    </div>

    <div id="successSentGuideRequest" class="transferito__hide-element">
        <?php echo loadTemplate( 'parts/notice', [
            'image'             => 'sent-success',
            'type'              => 'success',
            'messageTitle'      => 'Thank You!',
            'message'           => 'We are currently in the process of creating a guide for your hosting provider. You will be notified via email once it has been completed.',
            'closeButton'       => true,
        ]); ?>
    </div>

</div>
