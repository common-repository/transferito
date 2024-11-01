<?php if ($data['showEmptyKeyMessage']) : ?>
	<div class="empty-api-keys">
		<div class="add-keys-icon">+</div>
		<div class="add-keys-text">
			<p class="add-keys-title">
				You haven’t added any api keys to connect to our transferito servers
			</p>
			<p class="add-keys-content">
				If you already have an account with us, please enter your api keys.
				If you don’t please create an account to start using Transferito.
			</p>
		</div>
		<div class="add-keys-button-container">
			<a href="admin.php?page=transferito-settings" class="transferito-button">Setup API Keys</a>
			<a target="_blank" href="https://transferito.com/upgrade-to-premium" class="transferito-button transferito-left-button">Create account</a>
		</div>
	</div>
<?php endif; ?>
