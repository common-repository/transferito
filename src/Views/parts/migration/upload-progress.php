<div class="transferito-notice mt200">
    <div class="transferito__font--medium transferito__font--bold transferito__font--center">
	    <?php echo $data['title']; ?>
    </div>

    <div class="transferito__font--medium transferito__font--center">
	    <?php echo $data['mainMessage']; ?>
    </div>

    <div class="transferito__font--medium transferito__font--center">
		<?php echo $data['secondaryMessage']; ?>
    </div>

    <div id="codebaseProgressBar" class="transferito-progressbar transferito-hide mt40">
       <div class="transferito-progressbar__container">
           <div class="transferito-progressbar__fill"></div>
       </div>
        <div class="transferito-progressbar__info transferito__font--small transferito__font--super-bold">
            <div class="transferito-progressbar__label">BACKUP UPLOAD PROGRESS</div>
            <div class="transferito-progressbar__percentage">
                <span class="transferito-progressbar__percentage--number">0</span>%
            </div>
        </div>
    </div>

    <div id="databaseProgressBar" class="transferito-progressbar transferito-hide mt20">
       <div class="transferito-progressbar__container">
           <div class="transferito-progressbar__fill"></div>
       </div>
        <div class="transferito-progressbar__info transferito__font--small transferito__font--super-bold">
            <div class="transferito-progressbar__label">DATABASE UPLOAD PROGRESS</div>
            <div class="transferito-progressbar__percentage">
                <span class="transferito-progressbar__percentage--number">0</span>%
            </div>
        </div>
    </div>

</div>
