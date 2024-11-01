<div class="wrap">

    <?php if ($data['hasAPIKeys']) : ?>
        <div class="transferito__margin-bottom--40">
            <?php echo loadTemplate( 'parts/notice', [
                'image'             => 'system-requirements',
                'type'              => 'warning',
                'messageTitle'      => 'You don\'t have any API Keys!',
                'message'           => 'Until you enter your Public and Secret API keys.<br />  You\'ll only be able to migrate sites upto 250MB.',
            ]); ?>
        </div>
    <?php endif; ?>

    <div class="transferito__one-column transferito__one-column-container transferito__one-column-container--center transferito__one-column-container--no-height">

        <div class="transferito__container">
            <div class="transferito-text__h1">Transferito Settings</div>
            <div class="transferito-text__p1--regular">Enter your API keys to start using Transferito</div>

            <form class="transferito-setting_form" method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'transferito_settings_group' );
                do_settings_sections( 'transferito-settings' );
                ?>
                <div class="transfer-button-container">
                    <?php submit_button('Update Settings', 'transferito-button button-primary button-large'); ?>
                </div>
            </form>
        </div>

    </div>

</div>
