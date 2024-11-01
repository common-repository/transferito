<div class="transferito-notice">

    <?php if (isset($data['title'])) : ?>
        <div class="transferito-notice__title transferito-text__h2">
            <?php echo $data['title']; ?>
        </div>
    <?php endif; ?>

    <div class="transferito-notice__container">

        <div class="transferito-notice__icon transferito-notice__icon--<?php echo $data['image']; ?>"></div>

        <?php if (isset($data['externalLink'])) : ?>
            <div class="transferito-notice__action-button transferito-notice__action-button--reduced-margins">
                <a target="_blank" href="<?php echo $data['externalLink']['linkURL']; ?>" class="transferito-button transferito-button__primary transferito-button--large"><?php echo $data['externalLink']['anchorText']; ?></a>
            </div>
        <?php endif; ?>

        <?php if (isset($data['messageTitle'])) : ?>
            <div class="transferito-notice__message-title transferito-notice__message-title--<?php echo $data['type']; ?> transferito-text__h2--bold">
                <?php echo $data['messageTitle']; ?>
            </div>
        <?php endif; ?>

        <div class="transferito-notice__message transferito-notice__message--<?php echo $data['type']; ?>">
            <span class="transferito-text__p--regular"><?php echo $data['message']; ?></span>
        </div>

        <?php if (isset($data['closeButton']) && $data['closeButton']) : ?>
            <div class="transferito-notice__action-button">
                <button class="transferito-button transferito-button__secondary transferito-button--medium transferito__modal--close">CLOSE</button>
            </div>
        <?php endif; ?>

        <?php if (isset($data['additionalInfo'])) : ?>
            <div class="transferito-notice__divider"></div>
        <?php endif; ?>

        <?php if (isset($data['additionalTitle'])) : ?>
            <div class="transferito-notice__additional-info-title transferito-text__h4">
                <?php echo $data['additionalTitle']; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($data['additionalInfo'])) : ?>
            <div class="transferito-notice__additional-info transferito-text__p">
                <?php echo $data['additionalInfo']; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($data['links'])) : ?>
            <div class="transferito-notice__action-button transferito-notice__action-button--column">
                <?php foreach ($data['links'] as $link) : ?>
                    <a target="_blank" class="transferito-button transferito-button__primary--blue transferito-button--small" href="<?php echo $link['url']; ?>">
                        <?php echo $link['anchorText']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($data['supportLink'])) : ?>
            <div class="transferito-notice__support-link">
                <a target="_blank" data-event-name="supportLink_(<?php echo $data['messageTitle']; ?>)" class="transferito-button transferito-button__support transferito-button__support--blue transferito-button--medium transferito-log-event" href="<?php echo $data['supportLink']['url']; ?>">
                    <?php echo $data['supportLink']['anchorText']; ?>
                </a>
            </div>
        <?php endif; ?>

    </div>

    <?php if (isset($data['extraInfo'])) : ?>
        <div class="transferito-notice__container transferito-notice__extra-info">
            <?php if (isset($data['extraInfo']['title'])) : ?>
                <div class="transferito-notice__extra-info-title transferito-text__h4">
                    <?php echo $data['extraInfo']['title']; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($data['extraInfo']['content'])) : ?>
                <div class="transferito-notice__extra-info-content transferito-text__p1--semi-bold">
                    <?php echo $data['extraInfo']['content']; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
