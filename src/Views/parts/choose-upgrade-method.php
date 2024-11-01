<input type="hidden" id="upgradeAccount" value="<?php echo wp_create_nonce("upgrade_account"); ?>">

<div class="transferito__one-column">
    <div class="transferito__one-column-container transferito-upgrade">
        <div class="transferito-upgrade__icon"></div>
        <div class="transferito-upgrade__title transferito-text__h1">UPGRADE TO PREMIUM</div>
        <div class="transferito-upgrade__text transferito-text__p1--regular">
            Your site is <strong><?php echo $data['size']; ?></strong>, which is larger than our <strong>250MB</strong> size limit
        </div>
        <div class="transferito-upgrade__text transferito-text__p1--regular">
            To continue with your migration You will need to upgrade to Transferito Premium
        </div>
        <div class="transferito-upgrade__action-button">
            <a href="https://transferito.com/upgrade-to-premium" class="transferito-button transferito-button__primary transferito-button--large transferito-log-event" data-event-name="upgradeToPremium_Screen" target="_blank">UPGRADE TO PREMIUM</a>
        </div>
    </div>
</div>





