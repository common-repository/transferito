<input type="hidden" id="prepareTransfer" value="<?php echo wp_create_nonce("prepare_migration_files"); ?>">
<input type="hidden" id="transferMethod" value="cpanel">
<input type="hidden" id="cpanelHost" value="<?php echo $data['URL']; ?>">
<input type="hidden" id="cpanelUser" value="<?php echo $data['username']; ?>">
<input type="hidden" id="cpanelPass" value="<?php echo $data['password']; ?>">
<input type="hidden" id="cpanelApiToken" value="<?php echo $data['apiToken']; ?>">
<input type="hidden" id="useApiToken" value="<?php echo $data['useApiToken']; ?>">

<div class="transferito__three-columns">
    <div class="transferito__column transferito__navigation-column">
        <?php echo loadTemplate( 'parts/migration/navigation', [
            'destinationURL'        => 'completed',
            'chooseMethod'          => 'completed',
            'cPanelAuthentication'  => 'completed',
            'selectDomain'          => 'active',
            'startMigration'        => 'disabled',
        ]); ?>
    </div>
    <div class="transferito__column transferito__main-column">
        <div class="transferito-text__h1">Select Domain for Migration</div>
        <div class="transferito__content-container">
            <div class="transferito-domain-selection">
                <div class="transferito-domain-selection__title transferito-text__p1--bold">
                    <span class="transferito-input__required">*</span>
                    Select Destination Domain
                </div>
                <div class="transferito-domain-selection__content transferito-text__small">
                    Please choose your destination domain. If you want to change the destination domain, you can easily select it from here.
                </div>
                <div class="transferito-domain-selection__input">
                    <select
                        name="cpanelDomain"
                        id="field__cpanelDomain"
                        class="transferito-input__dropdown transferito-input__dropdown--full-width transferito-input__dropdown--large">
                        <?php foreach ($data['domains'] as $domain) : ?>
                            <option value="<?php echo $domain; ?>" <?php echo ($domain === $data['domain']) ? 'selected="selected"' : '' ?>><?php echo $domain; ?></option>
                        <?php endforeach; ?>
                    </select>

                </div>
                <div class="transferito-domain-selection__action-buttons">
                    <button id="routeToCpanelAuthentication" data-screen-route="cpanelAuthentication" class="transferito-button transferito-button__secondary transferito-button--small transferito__screen-routing">BACK</button>
                    <button id="cpanelStart" class="transferito-button transferito-button__primary transferito-button--small transferito__cpanel-start-migration">START MIGRATION</button>
                </div>
            </div>
        </div>
    </div>
    <div class="transferito__column transferito__pro-tip-column">
        <?php echo loadTemplate( 'parts/migration/pro-tip', [
            'mainText'  => 'Select the domain you want to migrate your current website. <br><br>If a WordPress website exists on your destination server. It will be replaced by this site that you are migrating.',
        ]); ?>
    </div>
</div>

<div id="cPanelDomainSelectFailure" class="transferito__hide-element">
    <?php echo loadTemplate( 'parts/notice', [
        'image'             => 'failed-auth',
        'type'              => 'error',
        'messageTitle'      => 'We\'re unable to create your FTP & Database details!',
        'message'           => 'There has a been an error.',
        'closeButton'       => true,
        'additionalInfo'    => 'If you are still facing this issue our migration specialists are happy to help you resolve this issue.',
        'supportLink'       => [
            'anchorText'    => 'Create Support Ticket',
            'url'           => 'https://wordpress.org/support/plugin/transferito/'
        ]
    ]); ?>
</div>
