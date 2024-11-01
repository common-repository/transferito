<div class="transferito__one-column">
    <div class="transferito__one-column-container transferito-loader">
        <?php if (!isset($data['showMigrationImage'])) : ?>
            <div class="transferito-loader__icon"></div>
        <?php endif; ?>

        <div class="transferito-loader__text transferito-text__p1--semi-bold">
            <strong><?php echo $data['mainMessage']; ?></strong>
        </div>
        <?php if (isset($data['secondaryMessage'])) : ?>
            <div class="transferito-loader__text transferito-text__p1--regular">
                <?php echo $data['secondaryMessage']; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
