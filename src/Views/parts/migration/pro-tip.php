
<?php if (isset($data['textBox'])) : ?>
    <div class="transferito-pro-tip__highlighted-text-box">
        <?php if (isset($data['textBox']['content'])) : ?>
            <div class="transferito-text__p"><?php echo $data['textBox']['content']; ?></div>
        <?php endif; ?>
        <?php if (isset($data['textBox']['link']) && isset($data['textBox']['link']['anchorText']) && isset($data['textBox']['link']['modalName'])) : ?>
            <div data-transferito-modal="<?php echo $data['textBox']['link']['modalName']; ?>" class="transferito-text__p--semi-bold transferito-pro-tip__link transferito-open-modal">
                <?php echo $data['textBox']['link']['anchorText']; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($data['faqs'])) : ?>
    <div class="transferito-pro-tip__title transferito-text__h3">FAQs:</div>
    <ul class="transferito__list--links">
        <?php foreach ($data['faqs'] as $faq) : ?>
            <?php if (isset($faq['anchorText']) && isset($faq['modalName'])) : ?>
                <li data-transferito-modal="<?php echo $faq['modalName']; ?>" class="transferito-text__p transferito-pro-tip__link transferito-open-modal">
                    <?php echo $faq['anchorText']; ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>



<?php if (isset($data['mainText'])) : ?>
    <div class="transferito-pro-tip__title transferito-text__h3">Pro Tip:</div>
    <p class="transferito-pro-tip__text transferito-text__p">
        <?php echo $data['mainText']; ?>
    </p>
<?php endif; ?>

<?php if (isset($data['secondaryText'])) : ?>
    <?php if (isset($data['secondaryText']['items'])) : ?>
        <div class="transferito-pro-tip__text transferito-text__p"><?php echo $data['secondaryText']['text']; ?></div>
    <?php endif; ?>
    <?php if (isset($data['secondaryText']['items'])) : ?>
        <ul class="transferito__list">
            <?php foreach ($data['secondaryText']['items'] as $item) : ?>
                <li><?php echo $item; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($data['otherTips'])) : ?>
    <div class="transferito-pro-tip__title transferito-text__h3">Other Tips:</div>
    <ul class="transferito__list">
        <?php foreach ($data['otherTips'] as $otherTip) : ?>
            <li class="transferito__list--added-margin"><?php echo $otherTip; ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>



