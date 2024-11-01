<div class="transferito-information">

    <?php if (isset($data['name'])) : ?>
        <input id="tutorialName" type="hidden" value="<?php echo $data['name']; ?>">
    <?php endif; ?>

    <div class="transferito-information__close-button transferito__modal--close"></div>

    <div class="transferito-information__container">

        <?php if (isset($data['title'])) : ?>
            <div class="transferito-information__title transferito-text__h3">
                <?php echo $data['title']; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($data['mainContent'])) : ?>
            <div class="transferito-information__content transferito-text__p1--regular">
                <?php echo $data['mainContent']; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($data['videoID'])) : ?>
            <div class="transferito-information__video">
                <iframe allowfullscreen id="ytplayer" type="text/html" width="500" height="320"
                        src="https://www.youtube.com/embed/<?php echo $data['videoID']; ?>?autoplay=0&fs=1&rel=0"
                        frameborder="0"></iframe>
            </div>
        <?php endif; ?>

        <?php if (isset($data['subTitle'])) : ?>
            <div class="transferito-information__sub-title transferito-text__p1--bold">
                <?php echo $data['subTitle']; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($data['steps']['options'])) : ?>
            <div class="transferito-information__dropdown">
                <select id="selectHostingProvider"
                        class="transferito-form-element transferito-input__dropdown transferito-input__dropdown--full-width transferito-input__dropdown--small">
                    <option value="" disabled selected>Select hosting provider</option>
                    <?php foreach ($data['steps']['options'] as $option) : ?>
                        <?php if (isset($option['videoID'])) : ?>
                            <option data-guide-video="<?php echo $option['videoID']; ?>" value="<?php echo $option['value']; ?>"><?php echo $option['text']; ?></option>
                        <?php else : ?>
                            <option value="<?php echo $option['value']; ?>"><?php echo $option['text']; ?></option>
                        <?php endif; ?>

                    <?php endforeach; ?>
                    <option value="not-listed">My hosting provider isn't listed</option>
                </select>
            </div>
        <?php endif; ?>

        <?php if (isset($data['guideMissingLink']) && $data['guideMissingLink']) : ?>
            <div class="transferito-information__content transferito-text__p1--semi-bold">
                Could’t find your hosting provider? <span data-transferito-modal="requestHostingGuideForm" class="transferito-information__link transferito-open-modal">Contact Us</span>
            </div>
        <?php endif; ?>

        <div id="transferitoGuideSteps" class="transferito-information__steps">

            <?php if (isset($data['steps']['generic'])) : ?>
                <ol class="transferito-information__steps-list">
                    <?php foreach ($data['steps']['generic'] as $genericList) : ?>
                        <li class="transferito-text__p1--regular">
                            <?php if (isset($genericList['text'])) : ?>
                                <span class="transferito-information__steps-text"><?php echo $genericList['text']; ?></span>
                            <?php endif; ?>
                            <?php if (isset($genericList['image'])) : ?>
                                <div class="transferito-information__steps-image">
                                    <img src="<?php echo TRANSFERITO_ASSET_URL . 'images/guides/' . $genericList['image']; ?>" alt="">
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <?php if (isset($data['steps']['guides'])) : ?>
                <?php foreach ($data['steps']['guides'] as $guideID => $guide) : ?>
                    <ol id="guideFor_<?php echo $guideID; ?>" class="transferito-information__steps-list transferito__hide-element">
                        <?php foreach ($guide as $guideList) : ?>
                            <li class="transferito-text__p1--regular">
                                <?php if (isset($guideList['text'])) : ?>
                                    <span class="transferito-information__steps-text"><?php echo $guideList['text']; ?></span>
                                <?php endif; ?>
                                <?php if (isset($guideList['image'])) : ?>
                                    <div class="transferito-information__steps-image">
                                        <img src="<?php echo TRANSFERITO_ASSET_URL . 'images/guides/' . $guideList['image']; ?>" alt="">
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <?php if (isset($data['additionalInfo'])) : ?>
            <?php if (isset($data['additionalInfo']['text'])) : ?>
                <div class="transferito-information__content transferito-information__content--with-divider transferito-text__p1--regular">
                    <?php echo $data['additionalInfo']['text']; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($data['additionalInfo']['links'])) : ?>
                <div class="transferito-information__links">
                    <ul>
                        <?php foreach($data['additionalInfo']['links'] as $link) :?>
                            <li class="transferito-text__p1--semi-bold transferito-open-modal" data-transferito-modal="<?php echo $link['modalName']; ?>">
                                <?php echo $link['text']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($data['guideRequest']) && $data['guideRequest']) : ?>
            <div class="transferito-information__form">
                <input id="hostingGuideName" type="hidden">
                <input type="hidden" id="hostingGuideDetails" value="<?php echo wp_create_nonce('hosting_guide_request_detail'); ?>">

                <div class="transferito-information__form-field">
                    <div class="transferito-information__form-label transferito-text__p1--bold">
                        Hosting Provider
                    </div>
                    <div class="transferito-information__form-label">
                        <input name="hostingProvider"
                               id="field__hostingProvider"
                               class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin" type="text">
                    </div>
                </div>
                <div class="transferito-information__form-field">
                    <div class="transferito-information__form-label transferito-text__p1--bold">
                        Your Email Address
                    </div>
                    <div class="transferito-information__form-field">
                        <input name="emailAddress"
                               id="field__emailAddress"
                               class="transferito__field-required transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin" type="text">
                    </div>
                </div>

                <div class="transferito-information__action-button">
                    <button id="fireRequestHostingGuide" class="transferito-button transferito-button__primary transferito-button--small transferito__request-hosting-guide" disabled>SUBMIT</button>
                </div>
            </div>
        <?php endif; ?>

    </div>



</div>
