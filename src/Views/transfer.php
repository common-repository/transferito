<div class="wrap">


	<?php if (!$data['hasAPIKeys']) : ?>

        <div id="startTransfer">
            <h1>Transfer a site</h1>

            <div id="transferitoError" class="notice notice-error" style="display: none">
                <p>Ooops... We were unable to start your transfer. Reason: <span class="bold" id="transferitoErrorMessage"></span> </p>
            </div>

            <div id="transferitoSuccess" class="notice notice-success" style="display: none">
                <p>Yayyy... We've started your transfer.</p>
            </div>

            <form class="transferito-setting_form mt25">

                <div class="selected-folder-container white-block mb30">
                    <div class="transfer-block separate-block-line">
                        <div class="transferito__font--medium transferito__font--bold">Start Transfer</div>
                        <div class="transferito__font--small-medium">
                            To start your transfer, enter your token and click the <strong>Start transfer</strong> button!
                        </div>
                    </div>

                    <table id="tokenDetails" class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row">Transfer Token</th>
                            <td>
                                <input id="action" type="hidden" value="preparing_transfer">
                                <input
                                        id="transferitoToken"
                                        type="text"
                                        class="transferito-form-element transferito-input"
                                        name="transferitoToken"
                                        placeholder="Enter token">
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="transfer-button-container">
                    <div class="transferito-button button button-primary button-large" id="fireTransfer">Start transfer</div>
                </div>
            </form>
        </div>

        <div id="transferStartProgress" class="transferito-hide">
	        <?php echo loadTemplate('parts/transfer-progress', array('showFirstSpinner' => true)); ?>
        </div>

	<?php endif; ?>




</div>
