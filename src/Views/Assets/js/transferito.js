(function($) {

    $(document).ready(function(){

        function Transferito() {
            this.mappedStatus = null;
            this.validated = {
                ftp: false,
                database: false
            };
            this.ftpPath = '';
            this.localUploadComplete = {
                database: false,
                codebase: false
            };
            this.transferMethodOptions = {
                method: '',
                cPanelAllowed: false,
            };
            this.currentStatus = 'backup.completed';
            this.backupSize = 0;
            this.backupPercentage = {
                codebase: 0,
                codebaseIncrement: 0,
                database: 0,
                databaseIncrement: 0,
            };
            this.utilities = {
                selector: $('#transferitoTemplate'),
                modalSelector: $('#transferitoModalTemplate'),
                migrationSteps: {
                    amount: 0,
                    currentStep: -1,
                    stepSplit: 0
                },
                removeTrailingSlashes: function(url) {
                    return url.replace(/\/+$/, '');
                },
                removeProtocols: function(host) {
                    return host.replace(/sftp:\/\/|ftp:\/\//, '');
                },
                getFormValues: function(object, item) {
                    if (item.name.indexOf('[]') !== -1) {
                        var key = item.name.replace('[]', '');
                        var value = item.value;
                        (!object[key]) ? object[key] = [value] : object[key].push(value);
                    } else {
                        object[item.name] = (item.value === 'on') ? true : item.value;
                    }
                    return object;
                },
                validateFormFields: function() {
                    var errorCount = 0;
                    $('.transferito__field-required:visible').each(function (index, formElement) {
                        var formSelector = $(formElement);

                        /**
                         * If the field is empty
                         * Show the error
                         */
                        if (!formSelector.val()) {
                            errorCount += 1;
                        }
                    });
                    return errorCount === 0;
                },
                validateURL: function() {
                    var cleanedURL = this.selector.find('#domain').val().trim().replace(/\/$/, "");
                    var domain = cleanedURL.split('//');
                    return domain.length === 2 && ['http:', 'https:'].indexOf(domain[0]) !== -1;
                },
                changeTemplate: function(action, nonce, extraData = {}, clearTemplate = false) {
                    var self = this;
                    var data = {
                        action: action,
                        actionKey: nonce,
                        data: extraData
                    };

                    if (clearTemplate) {
                        this.setTemplate('');
                    }

                    var templateChange = $.post(ajaxurl, data, function(response) {
                        var theResponse = response.data;
                        var hasAdditionalData = theResponse.hasOwnProperty('additionalData');
                        var template = theResponse.hasOwnProperty('htmlTemplate')
                            ? theResponse.htmlTemplate
                            : '';
                        self.setTemplate(template);

                        /**
                         * Show the legend message
                         */
                        if (hasAdditionalData) {
                            self.displayHeaderLegend(null, theResponse.additionalData.mainMessage);
                        }

                    });
                    templateChange.fail(function(data) {
                        self.setTemplate('Something has gone wrong - Please refresh the page and try again');
                    });
                },
                setTemplate: function(template, elementSelector = false, fadeInTime = 1500, customContent = false) {
                    /**
                     * If the selector isn't present
                     * Default to replace the whole screen
                     */
                    if (!elementSelector) {
                        this.selector.html(template).fadeIn(1500);
                    }

                    /**
                     * Replace the HTML in the specified selector
                     */
                    if (elementSelector) {
                        elementSelector.html(template).fadeIn(fadeInTime);

                        /**
                         * If the customContent argument has been provided
                         * Update the modal content & the relevant selector
                         */
                        if (customContent) {
                            elementSelector.find(customContent.selector).html(customContent.content);
                        }
                    }
                },
                loadingOnlyHTML: function() {
                    var template = '<div class="transferito__one-column-container transferito__one-column-container--no-width transferito-loader">';
                    template += '<div class="transferito-loader__icon transferito-loader__icon--no-bottom-margin"></div>';
                    template += '</div>';
                    return template;
                },
                loadingScreenHTML: function(message, subMessage) {
                    var template = '<div class="transferito__one-column">';
                    template += '<div class="transferito__one-column-container transferito-loader">';
                    template += '<div class="transferito-loader__icon"></div>';
                    template += '<div class="transferito-loader__text transferito-text__p1--semi-bold">';
                    template += message;
                    template += '</div>';

                    if (subMessage) {
                        template += '<div class="transferito-loader__text transferito-text__p1--regular">';
                        template += subMessage;
                        template += '</div>';
                    }

                    template += '</div>';
                    template += '</div>';
                    return template;
                },
                showLoadingScreen: function(message = 'Loading...', subMessage) {
                    this.selector.html(this.loadingScreenHTML(message, subMessage));
                },
                buildPayload: function () {
                    var self = this;
                    var formElements = [];

                    /**
                     * Create array of form elements that aren't hidden
                     * Include check for hidden form elements
                     */
                    $('.transferito-form-element').each(function(index, formElement) {
                        var formSelector = $(formElement),
                            isVisible = formSelector.closest('table').css('display');

                        if (isVisible === 'table' || isVisible === undefined) {
                            formElements.push(formElement);
                        }
                    });

                    /**
                     * Turn array of form elements
                     * Into object with input names as properties & input values as value
                     */
                    return $(formElements)
                        .serializeArray()
                        .reduce(function(object, item) {
                            return self.getFormValues(object, item)
                        }, {});
                },
                filterPayload: function(payload, allowedProperties = []) {
                    /**
                     * Check if there are keys to filter
                     */
                    if (allowedProperties.length === 0) {
                        return payload;
                    }

                    for (var property in payload) {
                        if (payload.hasOwnProperty(property) && !allowedProperties.includes(property)) {
                            delete payload[property];
                        }
                    }

                    return payload;
                },
                setTransferMethodOptions: function (method, cPanelAllowed) {
                    transferito.transferMethodOptions.method = method;
                    transferito.transferMethodOptions.cPanelAllowed = cPanelAllowed;
                },
                checkMigrationProgressSteps: function () {
                    this.migrationSteps.amount = $('.transferito-migration-progress__step').not(':hidden').length - 1;
                    this.migrationSteps.stepSplit = Math.round(100 / this.migrationSteps.amount);
                },
                updateCurrentMigrationProgressStep: function() {
                    this.migrationSteps.currentStep = this.migrationSteps.currentStep + 1;
                    this.updateMigrationOverviewProgressPercentage(
                        this.migrationSteps.currentStep * this.migrationSteps.stepSplit
                    );
                },
                calculateOverviewProgressPercentage: function (percentage) {
                    var percentageAsDecimal = (percentage / 100);
                    var stepPercentage = Math.round(this.migrationSteps.stepSplit * percentageAsDecimal);
                    var currentOverviewPercentage = this.migrationSteps.currentStep * this.migrationSteps.stepSplit;
                    var total = currentOverviewPercentage + stepPercentage;

                    return (total > 99) ? 100 : total;
                },
                updateMigrationOverviewProgressPercentage: function(percentage) {
                    $('#progressOverviewPercentage').html(percentage);
                    $('.transferito-migration-progress__bar--value').css('width', percentage + '%');
                },
                updateProgressStep: function(status, selector, statusChanged) {
                    /**
                     * If the status doesn't equal one of the allowed
                     */
                    if (status !== 'completed' && status !== 'active') {
                        return false;
                    }

                    var elementID = selector.attr('id');
                    var hiddenElementClass = 'transferito__hide-element';
                    var disabledStepClass = 'transferito-migration-progress__disabled-text';

                    selector.removeClass(disabledStepClass);

                    /**
                     * If it's active only add the class
                     */
                    if (status === 'active') {
                        /**
                         * Move the counter to the next migration step when a step has been initialised
                         */
                        if (statusChanged) {
                            /**
                             * Log event
                             */
                            this.logEvent('migrationStatus', {
                                status: elementID
                            });

                            this.updateCurrentMigrationProgressStep();
                        }

                        /**
                         * Change the static image to a spinner on this step
                         */
                        if (elementID === 'progress__finalizingWPInstall') {
                            selector
                                .find('.transferito-migration-progress__step-percent > .transferito-migration-progress__final-step')
                                .removeClass('transferito-migration-progress__final-step--static')
                        }
                    }

                    /**
                     * If it's completed remove the active and add the completed
                     */
                    if (status === 'completed') {
                        /**
                         * Update the overview % to 100%
                         */
                        if (elementID === 'progress__finalizingWPInstall') {
                            this.updateMigrationOverviewProgressPercentage(100);
                        }

                        selector.find('.transferito-migration-progress__step-icon').removeClass(hiddenElementClass);
                        selector.find('.transferito-migration-progress__step-percent').addClass(hiddenElementClass);
                    }
                },
                updateProgressPercentage: function(percentage, selector) {
                    /**
                     * If percentage is null
                     */
                    if (!percentage) {
                        return false;
                    }

                    /**
                     * Calc the overview moving percentage
                     * Then update it
                     */
                    this.updateMigrationOverviewProgressPercentage(
                        this.calculateOverviewProgressPercentage(percentage)
                    );

                    /**
                     * Update the percentage for the progress bar
                     */
                    selector.html(percentage);
                },
                hideProgressPercentage: function(selector) {
                    /**
                     * Update the percentage for the progress bar
                     */
                    selector.closest('.transferito-migration-progress__step-percent').addClass('transferito__hide-element');
                },
                round: function(value, precision) {
                    var multiplier = Math.pow(10, precision || 0);
                    return Math.round(value * multiplier) / multiplier;
                },
                saveBackupPercentage: function(key, options) {
                    transferito.backupPercentage[key] = options.percentage;
                    transferito.backupPercentage[key + 'Increment'] = this.round(options.increment);
                },
                updateTheBackupProgress: function(type) {
                    transferito.backupSize += transferito.backupPercentage[type + 'Increment'];
                    var archiveSize = this.round(transferito.backupSize);
                    var usedArchiveSize = archiveSize > 99 ? 99 : archiveSize;
                    this.updateProgressPercentage(usedArchiveSize, $('#backupInstallationProgressPercentage'));
                },
                updateTheExecBackupProgress: function(amount, initialAmount) {
                    /**
                     * If the amount is truthy
                     * & it is greater than the initialAmount then & only then my friend should we proceed
                     */
                    if (amount && amount > initialAmount) {
                        /**
                         * If the amount is greater than the backup size
                         * Then we continue -
                         * The percentage should never decrease
                         */
                        if (amount > transferito.backupSize) {
                            transferito.backupSize = amount;
                            var archiveSize = this.round(transferito.backupSize);
                            var usedArchiveSize = archiveSize > 99 ? 99 : archiveSize;
                            this.updateProgressPercentage(usedArchiveSize, $('#backupInstallationProgressPercentage'));
                        }
                    }
                },
                changeProgressStep: function (migrationStatus, responseData, statusChanged) {
                    /**
                     * Move the status of the step
                     */
                    this.updateProgressStep(migrationStatus.status, migrationStatus.selector, statusChanged);

                    /**
                     * Check that the progress property exists and that the response data is there
                     */
                    if (migrationStatus.hasOwnProperty('progress') && responseData) {
                        var percentage = responseData.hasOwnProperty('metadata') && responseData.metadata && responseData.metadata.hasOwnProperty('value')
                            ? responseData.metadata.value
                            : null;

                        /**
                         * Update the progress
                         */
                        this.updateProgressPercentage(percentage, migrationStatus.progressSelector);
                    }

                    /**
                     * If progress completion property exists
                     */
                    if (migrationStatus.hasOwnProperty('progressComplete')) {
                        this.hideProgressPercentage(migrationStatus.progressSelector);
                    }
                },
                displayModal: function(modalName, customModalContent) {
                    /**
                     * Log event
                     */
                    this.logEvent('modalOpened', {
                        modalName: modalName
                    });

                    /**
                     * Get the HTML for the modal
                     */
                    var modalHTML = $('#' + modalName).html();

                    /**
                     * Remove the style attribute
                     */
                    this.modalSelector.removeAttr('style');

                    /**
                     * Remove the hidden element
                     */
                    this.modalSelector.removeClass('transferito__hide-element');

                    /**
                     * Move the HTML into the modal DIV
                     */
                    this.setTemplate(modalHTML, this.modalSelector, 500, customModalContent);
                },
                displayFormGuideModal: function(modalName, relatedGuideName) {
                    this.displayModal(modalName);
                    $('#hostingGuideName').val(relatedGuideName);
                },
                closeModal: function() {
                    /**
                     * Modal Selector
                     */
                    var modalTemplate = $('#transferitoModalTemplate');

                    /**
                     * Remove the hidden element
                     */
                    modalTemplate.addClass('transferito__hide-element');

                    /**
                     * Add the loading smaller loading indicator
                     */
                    this.setTemplate('', modalTemplate);

                    /**
                     * Remove the style attribute
                     */
                    modalTemplate.removeAttr('style');
                },
                displayMigrationProgressFailure: function (selector, message) {

                    var elementID = selector.attr('id');
                    var hiddenElementClass = 'transferito__hide-element';

                    /**
                     * Change the title
                     */
                    $('#migrationProgressTitle').html('Migration Failed');

                    /**
                     * Change the colour to red
                     */
                    $('#overviewProgressPercentageBar').addClass('transferito-migration-progress__bar--red');

                    /**
                     * Add the left align class
                     */
                    selector.addClass('transferito-migration-progress__step--left-align');

                    /**
                     * Display the main icon
                     * Hide the progress percentage
                     * Display the warning error icon
                     */
                    selector.find('.transferito-migration-progress__step-icon')
                        .html('<div class="transferito-icon transferito-icon--exclamation-mark"></div>')
                        .addClass('transferito-migration-progress__step-icon--extended');
                    selector.find('.transferito-migration-progress__step-percent').addClass(hiddenElementClass);
                    selector.find('.transferito-migration-progress__step-icon').removeClass(hiddenElementClass);

                    /**
                     * Display the error container
                     * & Add the message into the error container
                     */
                    selector.find('.transferito-migration-progress__step-title > .transferito-migration-progress__error-container')
                        .removeClass(hiddenElementClass)
                        .html(message);
                },
                buildMappedStatus: function() {
                    return {
                        'download.backup.started': {
                            progress: true,
                            progressSelector: $('#downloadBackupProgressPercentage'),
                            previous: [],
                            status: 'active',
                            selector: $('#progress__downloadingBackup')
                        },
                        'download.backup.completed': {
                            progressComplete: true,
                            progressSelector: $('#downloadBackupProgressPercentage'),
                            previous: [],
                            status: 'completed',
                            selector: $('#progress__downloadingBackup')
                        },
                        'extract.backup.started': {
                            progress: true,
                            progressSelector: $('#extractingBackupProgressPercentage'),
                            previous: ['download.backup.completed'],
                            status: 'active',
                            selector: $('#progress__extractingBackup')
                        },
                        'extract.backup.completed': {
                            progressComplete: true,
                            progressSelector: $('#extractingBackupProgressPercentage'),
                            previous: [
                                'download.backup.completed',
                                'extract.backup.started',
                            ],
                            status: 'completed',
                            selector: $('#progress__extractingBackup')
                        },
                        'import.database.started': {
                            progress: true,
                            progressSelector: $('#installDatabaseProgressPercentage'),
                            previous: [
                                'download.backup.completed',
                                'extract.backup.started',
                                'extract.backup.completed'
                            ],
                            status: 'active',
                            selector: $('#progress__installingWordPress')
                        },
                        'import.database.completed': {
                            progressComplete: true,
                            progressSelector: $('#installDatabaseProgressPercentage'),
                            previous: [
                                'download.backup.completed',
                                'extract.backup.started',
                                'extract.backup.completed',
                                'import.database.started'
                            ],
                            status: 'completed',
                            selector: $('#progress__installingWordPress')
                        },
                        'finalize.install': {
                            progressComplete: true,
                            progressSelector: $('#installDatabaseProgressPercentage'),
                            previous: [
                                'download.backup.completed',
                                'extract.backup.started',
                                'extract.backup.completed',
                                'import.database.started',
                                'import.database.completed'
                            ],
                            status: 'active',
                            selector: $('#progress__finalizingWPInstall')
                        },
                        'completed': {
                            previous: [
                                'download.backup.completed',
                                'extract.backup.started',
                                'extract.backup.completed',
                                'import.database.started',
                                'import.database.completed',
                                'finalize.install'
                            ],
                            status: 'completed',
                            selector: $('#progress__finalizingWPInstall')
                        },
                        'completed.with.errors': {
                            previous: [
                                'download.backup.completed',
                                'extract.backup.started',
                                'extract.backup.completed',
                                'import.database.started',
                                'import.database.completed',
                                'finalize.install'
                            ],
                            status: 'completed.with.errors',
                            selector: $('#progress__finalizingWPInstall')
                        }
                    };
                },
                displayHeaderLegend: function(state, message) {
                    var legendSelector = $('#transferitoHeaderLegend');
                    var legendState = {
                        error: 'transferito-legend--error',
                        warning: 'transferito-legend--warning',
                        success: 'transferito-legend--success'
                    };
                    var mappedLegendState = (!state) ? '' : legendState[state];

                    /**
                     * Remove all classes
                     */
                    legendSelector.removeClass();

                    /**
                     * Add the initial legend class back
                     */
                    legendSelector.addClass('transferito-legend');

                    /**
                     * Add the correct class on the element - if the state exists
                     */
                    if (mappedLegendState) {
                        legendSelector.addClass(mappedLegendState);
                    }

                    /**
                     * Add the message to the legend
                     */
                    legendSelector.html(message);
                },
                hideHeaderLegend: function() {
                    $('#transferitoHeaderLegend').addClass('transferito__hide-element')
                },
                logEvent: function(event, eventProperties) {
                    $.post(ajaxurl, {
                        action: 'log_transferito_event',
                        event,
                        eventProperties,
                    });
                }
            };

            /**
             *
             */
            this.checkSite = function() {
                this.utilities.changeTemplate('check_current_site', $('#nonce').val());
            };

            /**
             * Get the migration status
             *
             * @param token
             */
            this.getStatus = function (token) {
                var self = this;
                this.mappedStatus = this.utilities.buildMappedStatus();
                $.post(ajaxurl, { action: 'status_check', token: token }, function(response) {

                    /**
                     * If the status is false or null
                     * Retry the status check
                     *
                     * If not process the status as normal
                     */
                    if (!response.data) {
                        self.getStatus(token);
                    } else {

                        console.log(response.data);

                        /**
                         * Check the status has the properties to not fails
                         */
                        if (response.hasOwnProperty('data') && response.data.hasOwnProperty('completed')) {

                            /**
                             * Check the object has the status property
                             */
                            if (response.data.hasOwnProperty('status')) {

                                /**
                                 * Check if the status has changed
                                 */
                                var statusChanged =  self.currentStatus !== response.data.status;

                                /**
                                 * Assign the status to the current status
                                 */
                                self.currentStatus = response.data.status;

                                /**
                                 * Check that the status exists
                                 */
                                var migrationStatus = self.mappedStatus.hasOwnProperty(response.data.status)
                                    ? self.mappedStatus[response.data.status]
                                    : null;

                                /**
                                 * If the status exist - Update it
                                 */
                                if (migrationStatus) {
                                    /**
                                     * If the previous property exists
                                     */
                                    if (migrationStatus.hasOwnProperty('previous')) {
                                        for (let index = 0; index < migrationStatus.previous.length; index++) {
                                            var migrationPreviousStatus = self.mappedStatus[migrationStatus.previous[index]];

                                            /**
                                             * Change the progress step
                                             */
                                            self.utilities.changeProgressStep(migrationPreviousStatus);
                                        }
                                    }

                                    /**
                                     * Change the progress step
                                     */
                                    self.utilities.changeProgressStep(migrationStatus, response.data, statusChanged);
                                }
                            }

                            /**
                             * If the migration is still in progress
                             */
                            if (!response.data.completed) {
                                self.getStatus(token);
                            }

                            /**
                             * If the migration has completed
                             */
                            if (response.data.completed) {
                                self.utilities.hideHeaderLegend();
                                self.utilities.logEvent('migrationCompleted', {
                                    completed:  true
                                });
                                self.cleanUp(
                                    false,
                                    [],
                                    false,
                                    {
                                        status: response.data.status
                                    });
                            }

                        }
                    }

                })
                .fail(function(error) {
                    if (error.hasOwnProperty('status') && error.status > 501) {
                        setTimeout(function () {
                            self.getStatus(token);
                        }, 10000, self, token);
                    } else {

                        var migrationStatus = self.mappedStatus.hasOwnProperty(self.currentStatus)
                            ? self.mappedStatus[self.currentStatus]
                            : self.mappedStatus['download.backup.started'];

                        self.utilities.logEvent('failedMigration', {
                            migrationStatus: self.currentStatus,
                            errorMessage: error?.responseJSON?.data
                        });

                        self.cleanUp(
                            'USE_CUSTOM_ERROR_MESSAGE',
                            error.responseJSON,
                            false,
                            null,
                            migrationStatus.selector
                        );
                    }
                });
            };

            /**
             * Start the migration
             *
             * @param wpNonce
             */
            this.startMigration = function(wpNonce) {
                var self = this;
                var data = {
                    action: 'start_migration',
                    security: wpNonce
                };

                var sendFiles = $.post(ajaxurl, data, function(response) {
                    self.utilities.displayHeaderLegend('success', response.data.message);
                    self.getStatus(response.data.token);
                });
                sendFiles.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'prepareBackup',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__prepareBackup')
                    );
                });
            };

            /**
             * Start to prepare the migration
             *
             * @param migrationDetails
             * @param key
             */
            this.prepareMigration = function (migrationDetails, key) {
                var self = this;
                var data = {
                    action: 'preparing_transfer',
                    security: key,
                    migrationDetails: migrationDetails
                };
                var backup = $.post(ajaxurl, data, function(response) {
                    /**
                     * Change the template
                     */
                    self.utilities.setTemplate(response.data.htmlTemplate);

                    /**
                     * Update the legend message
                     */
                    self.utilities.displayHeaderLegend('warning', response.data.message);

                    /**
                     * Count how many steps exist
                     */
                    self.utilities.checkMigrationProgressSteps();

                    /**
                     * Set the active
                     */
                    self.utilities.updateProgressStep('active', $('#progress__prepareBackup'), true);

                    /**
                     * Start the ZIP
                     */
                    if (response.data.useZipFallback) {
                        self.prepareCodebaseBackup(key);
                    }

                    /**
                     * If we don't use the Fallback
                     */
                    if (!response.data.useZipFallback) {
                        /**
                         * Set prepare backup to completed
                         */
                        self.utilities.updateProgressStep('completed', $('#progress__prepareBackup'));

                        /**
                         * Set the backup started to active
                         */
                        self.utilities.updateProgressStep('active', $('#progress__backupInstallation'), true);

                        /**
                         * If the DB is excluded
                         * Go straight to the codebase archive
                         */
                        if (response.data.excludeDatabase) {
                            self.archiveCreationStart(key);
                        }

                        /**
                         * If the DB isn't excluded
                         */
                        if (!response.data.excludeDatabase) {
                            /**
                             * Set the initial percentage for the db
                             */
                            self.utilities.saveBackupPercentage('database', {
                                percentage: response.data.databasePercentage,
                                increment: 0,
                            });

                            /**
                             * Start the backup process
                             */
                            self.prepareDatabaseBackup(key, response.data.useZipFallback);
                        }
                    }
                });
                backup.fail(function(error) {
                    var transferMethod = data.migrationDetails.transferMethod;

                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation',
                        errorMessage: error?.responseJSON?.data
                    });

                    /**
                     * For cPanel migrations - Populate the modal with the current error message
                     * When the API is unable to create the FTP & DB details
                     */
                    if (transferMethod === 'cpanel') {
                        self.screenRouting(
                            'cpanelDomainSelection',
                            '',
                            '',
                            'cPanelDomainSelectFailure',
                            {
                                content: '<b>Reason:</b> ' + error?.responseJSON?.data,
                                selector: '.transferito-text__p--regular'
                            }
                        );
                    } else {
                        self.cleanUp(
                            'USE_CUSTOM_ERROR_MESSAGE',
                            error.responseJSON,
                            false,
                            null,
                            $('#progress__backupInstallation')
                        );
                    }

                });
            };

            /**
             * Prepare the file list for the archive creation
             *
             * @param key
             */
            this.prepareCodebaseBackup = function(key) {
                var self = this;
                var data = {
                    action: 'preparing_codebase',
                    security: key
                };
                var codebasePreparation = $.post(ajaxurl, data, function(response) {
                    /**
                     * Set prepare backup to completed
                     */
                    self.utilities.updateProgressStep('completed', $('#progress__prepareBackup'));

                    /**
                     * Set the backup started to active
                     */
                    self.utilities.updateProgressStep('active', $('#progress__backupInstallation'), true);

                    /**
                     * Create the Codebase percent and increment value
                     */
                    self.utilities.saveBackupPercentage('codebase', {
                        percentage: response.data.codebasePercentage,
                        increment: (response.data.codebasePercentage / response.data.amount),
                    });

                    /**
                     * Create the Database percent and increment value
                     */
                    self.utilities.saveBackupPercentage('database', {
                        percentage: response.data.databasePercentage,
                        increment: 0,
                    });

                    /**
                     * Start adding files to the codebase
                     */
                    self.addFilesToCodebaseBackup(key, 1, response.data.amount);
                });
                codebasePreparation.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - preparing_codebase',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            };

            /**
             * Add the files to the created archive
             *
             * @param key
             * @param fileIndex
             * @param maxAmount
             */
            this.addFilesToCodebaseBackup = function(key, fileIndex, maxAmount) {
                var self = this;

                if (fileIndex > maxAmount) {
                    self.codebaseCompleted(key);
                } else {
                    /**
                     * Update the progress for the codebase
                     */
                    self.utilities.updateTheBackupProgress('codebase');

                    var data = {
                        action: 'add_files_to_codebase_archive',
                        security: key,
                        currentFileIndex: fileIndex
                    };
                    var addFiles = $.post(ajaxurl, data, function(response) {
                        var newFileIndex = fileIndex + 1;
                        self.addFilesToCodebaseBackup(key, newFileIndex, maxAmount);
                    });
                    addFiles.fail(function(error) {
                        self.utilities.logEvent('failedMigration', {
                            migrationStatus: 'backupInstallation - add_files_to_codebase_archive',
                            errorMessage: error?.responseJSON?.data
                        });
                        self.cleanUp(
                            'USE_CUSTOM_ERROR_MESSAGE',
                            error.responseJSON,
                            false,
                            null,
                            $('#progress__backupInstallation')
                        );
                    });
                }
            }

            /**
             * Notification for codebase archive creation process
             *
             * @param key
             */
            this.codebaseCompleted = function(key) {
                var self = this;
                var data = {
                    action: 'codebase_completion',
                    security: key,
                };
                var codebaseCompletion = $.post(ajaxurl, data, function(response) {
                    self.checkArchiveCompletion(key);
                    /**
                     * Initialise the database archive process
                     * If the DB hasn't been excluded
                     */
                    if (!response.data.excludeDatabase) {
                        self.prepareDatabaseBackup(key, true);
                    }
                });
                codebaseCompletion.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - codebase_completion',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            }

            /**
             *  Prepare the DB to be backed up
             *
             * @param key
             * @param useZipFallback
             */
            this.prepareDatabaseBackup = function(key, useZipFallback) {
                var self = this;
                var data = {
                    action: 'preparing_database',
                    security: key
                };
                var databasePreparation = $.post(ajaxurl, data, function(response) {
                    self.chunkDBExport(key, true, useZipFallback);
                });
                databasePreparation.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - preparing_database',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            };

            /**
             * Create DB Export Files
             *
             * @param key
             * @param firstRun
             * @param useZipFallback
             */
            this.chunkDBExport = function(key, firstRun, useZipFallback) {
                var self = this;
                var data = {
                    action: 'create_db_exports',
                    security: key,
                    firstRun: firstRun
                };
                var chunkedExport = $.post(ajaxurl, data, function(response) {

                    /**
                     * Update our progress while we chunk
                     * If exec is being used
                     */
                    if (!useZipFallback && response.data.hasOwnProperty('tableIndex')) {

                        /**
                         * Only on the first run set the percentage
                         */
                        if (response.data.fileIndex === 2) {
                            self.utilities.saveBackupPercentage('database', {
                                percentage: transferito.backupPercentage.database,
                                increment: (transferito.backupPercentage.database / response.data.tableIndex),
                            });
                        }

                        /**
                         * Update the progress for the database
                         */
                        self.utilities.updateTheBackupProgress('database');
                    }

                    /**
                     * Keep running the export until the export flag is true
                     */
                    if (!response.data.completed) {
                        self.chunkDBExport(key, false, useZipFallback);
                    }

                    /**
                     * When the chunked export has been completed
                     * Notify the application that the DB has been completed
                     */
                    if (response.data.completed) {
                        self.databaseCompleted(key);
                    }
                });
                chunkedExport.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - create_db_exports',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            }

            /**
             * Notification for database export completion
             *
             * @param key
             */
            this.databaseCompleted = function(key) {
                var self = this;
                var data = {
                    action: 'database_completion',
                    security: key,
                };
                var databaseCompletion = $.post(ajaxurl, data, function(response) {
                    /**
                     * If we are using the zip fallback
                     * Run the archive completion
                     */
                    if (response.data.useZipFallback) {
                        self.checkArchiveCompletion(key);
                    }

                    /**
                     * If zip fallback isn't used - Run the db move
                     */
                    if (!response.data.useZipFallback) {
                        self.databaseFilesRelocation(key);
                    }
                });
                databaseCompletion.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - database_completion',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            }

            /**
             * Relocate the database files
             *
             * @param key
             */
            this.databaseFilesRelocation = function(key) {
                var self = this;
                var data = {
                    action: 'database_relocation',
                    security: key,
                };
                var databaseRelocation = $.post(ajaxurl, data, function(response) {
                    self.checkDatabaseRelocation(key);
                });
                databaseRelocation.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - database_relocation',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            }

            /**
             * Relocate the database files check
             *
             * @param key
             */
            this.checkDatabaseRelocation = function(key) {
                var self = this;
                var data = {
                    action: 'database_relocation_check',
                    security: key,
                };
                var databaseRelocationCheck = $.post(ajaxurl, data, function(response) {
                    /**
                     * If the DB isn't completed
                     * Keep Polling until the job has completed
                     */
                    if (!response.data.completed) {
                        self.checkDatabaseRelocation(key);
                    }

                    /**
                     * Once completed
                     * Start the Archive creation
                     */
                    if (response.data.completed) {
                        self.backupSize = response.data.siteInfo.databasePercentage;
                        self.utilities.updateProgressPercentage(response.data.siteInfo.databasePercentage, $('#backupInstallationProgressPercentage'));
                        self.archiveCreationStart(key);
                    }
                });
                databaseRelocationCheck.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - database_relocation_check',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            }

            /**
             * Start the creation of the Archive
             *
             * @param key
             */
            this.archiveCreationStart = function(key) {
                var self = this;
                var data = {
                    action: 'archive_creation',
                    security: key,
                };
                var archiveCreation = $.post(ajaxurl, data, function() {
                    self.checkArchiveProgress(key, transferito.backupSize);
                });
                archiveCreation.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - archive_creation',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            }

            /**
             * Check the progress of the archive creation
             *
             * @param key
             * @param initial
             */
            this.checkArchiveProgress = function(key, initial) {
                var self = this;
                var data = {
                    action: 'archive_progress_check',
                    security: key,
                };
                var archiveProgressCheck = $.post(ajaxurl, data, function(response) {

                    /**
                     * Update the amount
                     */
                    self.utilities.updateTheExecBackupProgress(response.data.progress, initial);

                    /**
                     * Poll every 5 second for the status
                     */
                    if (!response.data.completed) {
                        setTimeout(function () {
                            self.checkArchiveProgress(key, initial);
                        }, 2500, self, key, initial);
                    }

                    /**
                     * Once the Archive has been completed progress
                     */
                    if (response.data.completed) {
                        self.prepareMigrationStart(response.data.information);
                    }

                });
                archiveProgressCheck.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - archive_progress_check',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            }

            /**
             * Archive Database exports
             *
             * @param key
             * @param information
             */
            this.archiveDatabaseExports = function(key, information) {
                var self = this;
                var data = {
                    action: 'archive_db_exports',
                    security: key,
                };
                var exportArchive = $.post(ajaxurl, data, function(response) {

                    /**
                     * Create the Database percent and increment value
                     */
                    self.utilities.saveBackupPercentage('database', {
                        percentage: transferito.backupPercentage.database,
                        increment: (transferito.backupPercentage.database / response.data.amount),
                    });

                    self.addDBExportsToCodebaseBackup(key, 1, response.data.amount, information)
                });
                exportArchive.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - archive_db_exports',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            }

            /**
             * Add the files to the created archive
             *
             * @param key
             * @param fileIndex
             * @param maxAmount
             * @param information
             *
             * @todo Refactor to pass in a callback and request options - so "addFilesToCodebaseBackup" isn't duplicated
             */
            this.addDBExportsToCodebaseBackup = function(key, fileIndex, maxAmount, information) {
                var self = this;

                if (fileIndex > maxAmount) {
                    self.prepareMigrationStart(information);
                } else {

                    /**
                     * Update the progress for the database
                     */
                    self.utilities.updateTheBackupProgress('database');

                    var data = {
                        action: 'add_files_to_codebase_archive',
                        security: key,
                        currentFileIndex: fileIndex,
                        addDatabaseExports: 1
                    };
                    var addFiles = $.post(ajaxurl, data, function(response) {
                        var newFileIndex = fileIndex + 1;
                        self.addDBExportsToCodebaseBackup(key, newFileIndex, maxAmount, information);
                    });
                    addFiles.fail(function(error) {
                        self.utilities.logEvent('failedMigration', {
                            migrationStatus: 'backupInstallation - add_files_to_codebase_archive',
                            errorMessage: error?.responseJSON?.data
                        });
                        self.cleanUp(
                            'USE_CUSTOM_ERROR_MESSAGE',
                            error.responseJSON,
                            false,
                            null,
                            $('#progress__backupInstallation')
                        );
                    });
                }
            }

            /**
             * Fire the function to decide what action to perform when the archive creation is completed
             *
             * @param key
             */
            this.checkArchiveCompletion = function(key) {
                var self = this;
                var data = {
                    action: 'check_archive_completion',
                    security: key,
                };
                var archiveCompletion = $.post(ajaxurl, data, function(response) {
                    /**
                     * If the backup has completed
                     * The zip export flag is truthy
                     * Fire the start db zip export
                     */
                    if (response.data.backupComplete && response.data.zipDatabaseExport) {
                        self.archiveDatabaseExports(key, response.data.information);
                    }

                    /**
                     * If the backup has been completed
                     * The zip export flag is falsy
                     * Upload or start the migration
                     */
                    if (response.data.backupComplete && !response.data.zipDatabaseExport) {
                        self.prepareMigrationStart(response.data.information);
                    }
                });
                archiveCompletion.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'backupInstallation - check_archive_completion',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'USE_CUSTOM_ERROR_MESSAGE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__backupInstallation')
                    );
                });
            }

            /**
             * Make a decision on whether to do a direct migration or upload
             *
             * @param information
             */
            this.prepareMigrationStart = function (information) {
                /**
                 * Set the backup started to active
                 */
                this.utilities.updateProgressStep('completed', $('#progress__backupInstallation'));

                /**
                 * Hide the percentage on completion of codebase & database
                 */
                this.utilities.hideProgressPercentage($('#backupInstallationProgressPercentage'));

                /**
                 * Fire the local upload to have an accessible file
                 */
                if (information.uploadFiles) {
                    /**
                     * Set the backup to active
                     */
                    this.utilities.updateProgressStep('active', $('#progress__uploadBackup'), true);

                    /**
                     * Start the upload
                     */
                    this.startUpload(information.securityKey);
                }

                /**
                 * Start the migration
                 */
                if (!information.uploadFiles) {
                    this.startMigration(information.securityKey);
                }
            }

            /**
             * Start the local upload
             * @param wpNonce
             */
            this.startUpload = function(wpNonce) {
                var self = this;
                var data = {
                    action: 'initiate_local_upload',
                    security: wpNonce
                };
                var startLocalUpload = $.post(ajaxurl, data, function(response) {
                    /**
                     * Fire the process upload for the codebase
                     */
                    self.processLocalUpload(response.data.backup.archive, '#codebaseProgressBar');
                });
                startLocalUpload.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'uploadBackup - initiate_local_upload',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'UPLOAD_START_FAILURE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__uploadBackup')
                    );
                });
            };

            /**
             * Process the Local upload
             *
             * @param uploadDetail
             * @param progressSelector
             * @param ignoreLocalUploadProperties - If the DB is excluded ignores the upload flags and process the start
             * StartMigration method straight after the completeUpload has succeeded
             */
            this.processLocalUpload = function(uploadDetail, progressSelector, ignoreLocalUploadProperties = false) {
                /**
                 * Start the chunk upload
                 */
                this.uploadChunk(1, uploadDetail, progressSelector, ignoreLocalUploadProperties);
            };

            /**
             * Upload the chunk
             *
             * @param part
             * @param uploadDetail
             * @param progressSelector
             * @param ignoreLocalUploadProperties - If the DB is excluded ignores the upload flags and process the start
             * StartMigration method straight after the completeUpload has succeeded
             */
            this.uploadChunk = function(part, uploadDetail, progressSelector, ignoreLocalUploadProperties = false) {
                var self = this;
                var data = {
                    action: 'upload_chunk',
                    uploadId: uploadDetail.uploadId,
                    archiveType: uploadDetail.type,
                    partNumber: part
                };
                var chunkUpload = $.post(ajaxurl, data, function(response) {

                    /**
                     * Get all the parts
                     */
                    var maxParts = uploadDetail.parts;

                    /**
                     * Calculate the percentage of the uplaod
                     */
                    var percentage = Math.ceil((part / maxParts) * 100);


                    /**
                     * Update the percentage for the progress bar
                     */
                    self.utilities.updateProgressPercentage(percentage, $('#progressPercentage'));

                    /**
                     * Call the chunk upload recursively
                     */
                    if (part < maxParts) {
                        var nextPart = part + 1;
                        self.uploadChunk(nextPart, uploadDetail, progressSelector, ignoreLocalUploadProperties);
                    }

                    /**
                     * Complete the upload
                     */
                    if (part === maxParts) {
                        self.completeUpload(uploadDetail.uploadId, uploadDetail.type, ignoreLocalUploadProperties);
                    }

                });
                chunkUpload.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'uploadBackup - upload_chunk',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'UPLOAD_CHUNK_FAILURE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__uploadBackup')
                    );
                });
            };

            /**
             * Fire the complete upload to finalize the upload process
             *
             * @param uploadId
             * @param type
             * @param ignoreLocalUploadProperties - If the DB is excluded ignores the upload flags and process the start
             * StartMigration method straight after the completeUpload has succeeded
             */
            this.completeUpload = function(uploadId, type, ignoreLocalUploadProperties) {
                var self = this;
                var data = {
                    action: 'complete_upload',
                    uploadId: uploadId,
                    archiveType: type
                };
                var completeUpload = $.post(ajaxurl, data, function(response) {

                    /**
                     * Set the backup to completed
                     */
                    self.utilities.updateProgressStep('completed', $('#progress__uploadBackup'));

                    /**
                     * Hide the progress step
                     */
                    self.utilities.hideProgressPercentage($('#progressPercentage'));

                    /**
                     * If the ignore upload properties flag has been set
                     * Start the migration right away
                     */
                    if (ignoreLocalUploadProperties) {
                        self.startMigration(response.data.securityKey);
                    }

                    /**
                     * If both backup archives are present
                     * Update the flag
                     */
                    if (!ignoreLocalUploadProperties) {
                        self.localUploadComplete[type] = true;

                        /**
                         * Start the migration
                         */
                        self.startMigration(response.data.securityKey);
                    }
                });
                completeUpload.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'uploadBackup - complete_upload',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.cleanUp(
                        'UPLOAD_COMPLETION_FAILURE',
                        error.responseJSON,
                        false,
                        null,
                        $('#progress__uploadBackup')
                    );
                });
            };

            /**
             * Clean up either fail or complete
             */
            this.cleanUp = function (
                hasError = false,
                errors = [],
                ignoreTemplateSwitch = false,
                metadata = null,
                selector = null
            ) {
                var self = this;
                $.post(ajaxurl, {
                    action: 'clean_up_files',
                    hasError: hasError,
                    errors: errors,
                    metadata: metadata
                })
                .always(function(response) {
                    /**
                     * Only change the template if the flag exists
                     */
                    if (!ignoreTemplateSwitch && typeof response.data.htmlTemplate === 'string') {
                        self.utilities.setTemplate(response.data.htmlTemplate);
                    }

                    /**
                     * Display inline error
                     */
                    if (!ignoreTemplateSwitch && typeof response.data.htmlTemplate !== 'string') {
                        self.utilities.displayMigrationProgressFailure(
                            selector,
                            response.data.htmlTemplate.error
                        );
                    }
                });
            };

            /**
             * Start looking for the WP installation directory
             */
            this.startDirectorySearch = function() {
                var self = this;
                var securityKey = $('#directoryKey').val();
                var directoryRequest = $.post(ajaxurl, {
                    action: 'start_directory_search',
                    securityKey: securityKey,
                });

                directoryRequest.done(function(response) {
                    var url = response?.data?.url;
                    var directoryCheckId = response?.data?.directoryCheckId;
                    self.getDirectoryCheckUpdate(url, directoryCheckId);
                });

                directoryRequest.fail(function(response) {
                    self.screenRouting(
                        'ftpAuthentication',
                        '',
                        '',
                        'errorFailedDirectorySearch'
                    );
                });
            }

            /**
             * Poll to update UI on the status of the directory check
             *
             * @param url
             * @param directoryCheckId
             */
            this.getDirectoryCheckUpdate = function(url, directoryCheckId) {
                var self = this;
                var securityKey = $('#directoryKey').val();
                var directoryRequest = $.post(ajaxurl, {
                    action: 'get_directory_check_update',
                    securityKey: securityKey,
                    url: url,
                    directoryCheckId: directoryCheckId
                });

                directoryRequest.done(function(response) {
                    /**
                     * If the check is still in process
                     */
                    if (!response.data.complete && !response.data.found) {
                        /**
                         * Prefix an empty path
                         */
                        var path = !response?.data?.path ? '/' : response.data.path;

                        /**
                         * Update the path in the UI
                         */
                        $('#currentFTPPathCheck').html(path);

                        /**
                         * Continue to poll
                         */
                        self.getDirectoryCheckUpdate(url, directoryCheckId)
                    }

                    /**
                     * If the directory has been found
                     */
                    if (response.data.complete && response.data.found) {
                        $('#manualDirectorySelection').prop('disabled', false);
                        $('#ftpDirectorySelector').addClass('transferito__hide-element');
                        $('#directorySelectionCheckSuccess').removeClass('transferito__hide-element');
                    }

                    /**
                     * The directory cant be found on this server
                     */
                    if (response.data.complete && !response.data.found) {
                        self.screenRouting('ftpAuthentication', '', '', 'errorDirectoryNotFound');
                    }

                    /**
                     * The directory check has completed with a failure
                     */
                    if (response.data.complete && response.data.failed) {
                        self.screenRouting('ftpAuthentication', '', '', 'errorDirectoryUpdateFailed');
                    }

                });

                /**
                 * The directory status update has failed
                 */
                directoryRequest.fail(function(response) {
                    self.screenRouting('ftpAuthentication', '', '', 'errorDirectoryUpdateFailed');
                });
            }

            /**
             * Check the URL - To see if cPanel is available
             *
             * @param domain
             * @param wpNonce
             * @param message
             */
            this.checkCpanelAvailability = function (domain, wpNonce, message, subMessage) {
                /**
                 * Show the loading screen
                 */
                this.utilities.showLoadingScreen(message, subMessage);

                /**
                 *
                 */
                var self = this;
                var data = {
                    action: 'check_cpanel_availability',
                    domain: domain,
                    securityKey: wpNonce
                };
                var cPanelCheck = $.post(ajaxurl, data, function(response) {
                    self.utilities.setTransferMethodOptions(response.data.transferMethod, response.data.cPanelAllowed);
                    self.utilities.setTemplate(response.data.htmlTemplate);
                });
                cPanelCheck.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'destinationURL',
                        errorMessage: error?.responseJSON?.data
                    });
                    /**
                     * Route to screen
                     * No message
                     * Display Error Modal
                     */
                    self.screenRouting('destinationURL', '', '', 'errorIncorrectModal');
                });
            };

            /**
             * Switch the migration method
             *
             * @param transferMethod
             * @param message
             * @param subMessage
             */
            this.switchMode = function(transferMethod, message, subMessage) {
                /**
                 * Show the loading screen
                 */
                this.utilities.showLoadingScreen(message, subMessage);

                /**
                 * Call the API to switch the transfer method
                 */
                var self = this;
                var data = {
                    action: 'switch_mode',
                    method: transferMethod
                };
                var switchMode = $.post(ajaxurl, data, function(response) {
                    self.utilities.setTransferMethodOptions(response.data.transferMethod, response.data.cPanelAllowed);
                    self.utilities.setTemplate(response.data.htmlTemplate);
                });
                switchMode.fail(function(error) {
                    /**
                     * @todo Remove - Display popup instead
                     */
                    self.cleanUp('SWITCH_METHOD_FAILED', error.responseJSON);
                });
            };

            /**
             * Auth the cPanel request and move to the next step
             *
             * @param securityKey
             * @param message
             * @param cpanelDetails
             * @param selector
             */
            this.cpanelAuthentication = function(securityKey, cpanelDetails, message, subMessage) {
                /**
                 * Show the loading screen
                 */
                this.utilities.showLoadingScreen(message, subMessage);

                /**
                 *
                 */
                var self = this;
                var data = {
                    action: 'cpanel_authentication',
                    auth: cpanelDetails,
                    securityKey: securityKey
                };
                var cpanelAuth = $.post(ajaxurl, data, function(response) {
                    /**
                     * Show the new template
                     */
                    self.utilities.setTemplate(response.data.template);

                });
                cpanelAuth.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'cPanelAuth',
                        errorMessage: error?.responseJSON?.data
                    });
                    /**
                     * Route to screen
                     * No message
                     * Display Error Modal
                     */
                    self.screenRouting('cpanelAuthentication', '', '', 'errorFailedCpanelAuth');
                });
            };

            /**
             * Validate the server detail
             *
             * @param securityKey
             * @param message
             * @param serverDetails
             * @param selector
             */
            this.manualServerDetailValidation = function(securityKey, serverDetails, message, subMessage) {
                /**
                 * Show the loading screen
                 */
                this.utilities.showLoadingScreen(message, subMessage);

                /**
                 * Validate the FTP details
                 */
                var self = this;
                var data = {
                    action: 'server_detail_validation',
                    serverDetails: serverDetails,
                    securityKey: securityKey
                };
                var serverDetailValidation = $.post(ajaxurl, data, function(response) {

                    /**
                     * Show the new template
                     */
                    self.utilities.setTemplate(response.data.template);

                    /**
                     * Disable the button
                     */
                    $('#manualDirectorySelection').prop('disabled', true);

                    /**
                     * Start directory check call
                     */
                    self.startDirectorySearch();
                });
                serverDetailValidation.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'ftpAuth',
                        errorMessage: error?.responseJSON?.data
                    });
                    /**
                     * Route to screen
                     * No message
                     * Display Error Modal
                     */
                    self.screenRouting('ftpAuthentication', '', '', 'errorFailedFTPAuth');
                });
            };

            /**
             * Validate the database details
             *
             * @param securityKey
             * @param message
             * @param databaseDetail
             * @param selector
             */
            this.databaseDetailValidation  = function(securityKey, databaseDetail, message, subMessage) {
                var self = this;

                /**
                 * Show the loading screen
                 */
                this.utilities.showLoadingScreen(message, subMessage);

                /**
                 * Validate the correct directory
                 */
                var data = {
                    action: 'database_detail_validation',
                    databaseDetail: databaseDetail,
                    securityKey: securityKey
                };
                var databaseValidation = $.post(ajaxurl, data, function(response) {
                    /**
                     * Begin the migration process
                     */
                    self.prepareMigration(response.data.migrationDetail, response.data.securityKey);
                });
                databaseValidation.fail(function(error) {
                    self.utilities.logEvent('failedMigration', {
                        migrationStatus: 'databaseAuth',
                        errorMessage: error?.responseJSON?.data
                    });
                    self.screenRouting('databaseAuthentication', '', '', 'errorFailedDatabaseAuth');
                });
            };

            /**
             * Load the directory Selector
             */
            this.loadDirectoryTemplate = function() {
                var self = this;
                $.post(ajaxurl, { action: 'load_directory_template' })
                .always(function(response) {
                    self.utilities.setTemplate(response.data.template, $('#manualScreenTemplate'));

                    /**
                     * Update the navigation
                     */
                    $('#transferitoNav__manualFTPDirectorySelect')
                        .removeClass('transferito-nav__item__indicator--in-complete')
                        .removeClass('transferito-nav__item__indicator--completed');

                    /**
                     * Fire directory call
                     */
                    self.getDirectories(response.data.path);
                });
            };

            /**
             * Hide the quick start popup once they click the close button
             */
            this.hideQuickStartPopup = function () {
                $.post(ajaxurl, {
                    action: 'hide_quickstart_popup'
                })
                .always(function(response) {})
            };

            /**
             * Redirect a user to a particular screen
             *
             * @param route
             * @param message
             * @param subMessage
             * @param showModal
             * @param customizedModalContent
             */
            this.screenRouting = function(route, message, subMessage, showModal, customizedModalContent) {
                /**
                 * Only show if a message exists
                 */
                if (message) {
                    /**
                     * Show the loading screen
                     */
                    this.utilities.showLoadingScreen(message, subMessage);
                }

                /**
                 * Log event
                 */
                this.utilities.logEvent('screenRouting', {
                    screen: route
                });

                var self = this;
                var data = {
                    action: 'screen_route_redirection',
                    route: route
                };

                var routeScreen = $.post(ajaxurl, data, function(response) {
                    self.utilities.setTemplate(response.data.htmlTemplate);

                    if (route === 'directorySelector') {
                        $('#manualDirectorySelection').prop('disabled', true);

                        /**
                         * Start directory check call
                         */
                        self.startDirectorySearch();
                    }

                    if (showModal) {
                        self.utilities.displayModal(showModal, customizedModalContent);
                    }
                });
                routeScreen.fail(function(error) {
                    self.utilities.logEvent('failedRouting', {
                        route: route
                    });
                    console.log('Failed to route');
                });
            };

            /**
             * Send the guide request
             */
            this.sendGuideRequestForm = function(hostingDetail, message, subMessage) {
                /**
                 * Add a loader into the modal Div
                 */
                this.utilities.setTemplate(this.utilities.loadingOnlyHTML(), this.utilities.modalSelector, 500);

                var self = this;
                var data = {
                    action: 'send_request_form',
                    data: hostingDetail,
                    securityKey: hostingDetail.securityKey
                };

                var sendRequest = $.post(ajaxurl, data);
                sendRequest.always(function() {
                    self.utilities.displayModal('successSentGuideRequest');
                });
            }
        }

        /**
         * Initialize
         */
        var transferito = new Transferito();

        /**
         * Check the status of the site
         * If it is a FREE Transfer or not
         */
        transferito.checkSite();

        /**
         *
         */
        transferito.utilities.selector.on('click', '.transferito-ftp-authentication__folder-expander', function () {
            var childItems = $(this).next('.transferito-ftp-authentication__sub-folders');
            if (childItems.length === 0) {
                return false;
            }
            childItems.toggle();
        });

        /**
         *
         */
        transferito.utilities.selector.on('change', '.show-selected-folder', function() {
            $('#selectedFoldersDetails').toggle();
        });

        /**
         * Hide or show the Database Fields
         * Based on whether the checkbox is ticked or not
         */
        transferito.utilities.selector.on('change', '#excludeDatabase', function() {
           var excludeDatabase = $(this).prop('checked');
           var databaseFields = $('.transferito-database-authentication__input-fields');
           databaseFields.toggleClass('transferito-database-authentication__input-fields--hide', excludeDatabase);
            $('#manualServerMigrationStart').prop('disabled', !transferito.utilities.validateFormFields());

        });

        /**
         * Remove the error from the validation fields
         */
        transferito.utilities.selector.on('keyup', '.transferito-input--error > .transferito-required', function() {
            var selector = $(this);
            if (selector.val()) {
                selector.parent().removeClass('transferito-input--error')
            }
        });

        /**
         * Validation check for the domain input field
         * Disable or Enable the button based on the validity of the domain field
         */
        transferito.utilities.selector.on('keyup', '#domain', function() {
            var domainEntry = $(this).val();
            var domainIsEmpty = domainEntry.length === 0;
            $('#cpanelCheck').prop('disabled', domainIsEmpty);
        });

        /**
         * Open the modal by ID
         */
        transferito.utilities.selector.on('click', '.transferito-open-modal', function() {
            var modalID = $(this).data('transferitoModal');
            transferito.utilities.displayModal(modalID);
        });

        /**
         * Open the modal by ID
         */
        transferito.utilities.modalSelector.on('click', '.transferito-open-modal', function() {
            var modalID = $(this).data('transferitoModal');
            transferito.utilities.displayModal(modalID);
        });

        /**
         * Close the open modal
         */
        transferito.utilities.modalSelector.on('click', '.transferito__modal--close', function() {
            transferito.utilities.closeModal();
        });

        /**
         * Check to see if we can use cPanel
         */
        transferito.utilities.selector.on('click', '.transferito__check-cpanel-availability', function() {
            /**
             * Disable the Button to stop double checks
             */
            $(this).prop('disabled', true);

            transferito.checkCpanelAvailability(
                $('#domainProtocol').val() + $('#domain').val(),
                $('#cPanelCheckSecurity').val(),
                'Please wait...',
                'We\'re just checking your URL'
            );
        });

        /**
         * Toggle the password visibility
         */
        transferito.utilities.selector.on('click', '.transferito__password-visibility', function() {

            var visibleClass = 'transferito__password-visibility--visible';
            var selector = $(this);
            var inputField = $('#' + selector.data('transferitoPasswordField'));
            var passwordMasked = !selector.hasClass(visibleClass);

            /**
             * If the password is masked
             */
            if (passwordMasked) {
                /**
                 * Add the visibility class
                 */
                selector.addClass(visibleClass);

                /**
                 * Change the input type
                 */
                inputField.prop('type', 'text');
            }

            /**
             * If the password is visible
             */
            if (!passwordMasked) {
                /**
                 * Remove the visibility class
                 */
                selector.removeClass(visibleClass);

                /**
                 * Change the input type
                 */
                inputField.prop('type', 'password');
            }
        });

        /**
         * Switch the transfer method
         */
        transferito.utilities.selector.on('click', '.transferito__switch-mode', function() {
            /**
             * Disable the Button to stop double checks
             */
            $(this).prop('disabled', true);

            /**
             * Switch the transfer method
             */
            transferito.switchMode(
                $(this).data('transferitoTransferMethod'),
                $(this).data('transferitoTransferMethodMessage')
            );
        });


        /**
         * Authenticate cPanel details
         */
        transferito.utilities.selector.on('click', '.transferito__cpanel-authentication', function() {
            /**
             * Disable the Button to stop double checks
             */
            $(this).prop('disabled', true);

            /**
             * Validate that all required form fields have been completed
             */
            var validateFields = transferito.utilities.validateFormFields();

            /**
             * If validation has passed
             */
            if (validateFields) {
                /**
                 * Get the cPanel information
                 */
                var cPanelDetails = transferito.utilities.buildPayload();

                /**
                 * Get the wpNonce
                 */
                var securityKey = $('#cPanelMigration').val();

                /**
                 * Start the authentication
                 */
                transferito.cpanelAuthentication(
                    securityKey,
                    cPanelDetails,
                    'Please wait..',
                    'We\'re just validating your cPanel details.',
                    $('#cPanelScreenTemplate')
                );

                $(this).prop('disabled', false);
            }

            /**
             * Enable the button if validation has failed
             */
            if (!validateFields) {
                $(this).prop('disabled', false);
            }
        });

        /**
         * Start the migration for cpanel
         */
        transferito.utilities.selector.on('click', '.transferito__cpanel-start-migration', function() {
            /**
             * Disable the Button to stop double migrations
             */
            $(this).prop('disabled', true);

            /**
             * The migration security key
             */
            var securityKey = $('#prepareTransfer').val();

            /**
             * Create the migration payload
             */
            var cpanelMigrationDetails = {
                transferMethod: 'cpanel',
                cpanelHost: $('#cpanelHost').val(),
                cpanelUser: $('#cpanelUser').val(),
                cpanelPass: $('#cpanelPass').val(),
                cpanelApiToken: $('#cpanelApiToken').val(),
                useApiToken: $('#useApiToken').val(),
                domain: $('#field__cpanelDomain').val()
            };

            /**
             * Set the loading spinner
             */
            transferito.utilities.setTemplate(
                transferito
                    .utilities
                    .loadingScreenHTML('Please wait...', 'We are just creating your FTP & Database details.')
            );

            /**
             * Start the migration
             */
            transferito.prepareMigration(cpanelMigrationDetails, securityKey);
        });

        /**
         * Validate main server details
         */
        transferito.utilities.selector.on('click', '.transferito__manual-server-details', function() {
            /**
             * Disable the Button to stop double checks
             */
            $(this).prop('disabled', true);

            /**
             * Validate that all required form fields have been completed
             */
            var validateFields = transferito.utilities.validateFormFields();

            /**
             * If validation has passed
             */
            if (validateFields) {
                /**
                 * Get the manual server details information
                 */
                var serverDetail = transferito.utilities.buildPayload();

                /**
                 * Get the wpNonce
                 */
                var securityKey = $('#manualMigrationServerDetail').val();

                /**
                 * Remove port numbers
                 */
                var hostProtocolSplit = serverDetail.ftpHost.split('://');

                /**
                 * Remove the protocol from the host
                 */
                var protocolRemoved = hostProtocolSplit.length === 1 ? hostProtocolSplit[0] : hostProtocolSplit[1];

                /**
                 * Remove the protocol
                 */
                var hostPortSplit = protocolRemoved.split(':');

                /**
                 * Assign the modified host
                 */
                serverDetail.ftpHost = hostPortSplit[0];

                /**
                 * Start the validation
                 */
                transferito.manualServerDetailValidation(
                    securityKey,
                    serverDetail,
                    'Please wait.. ',
                    'We\'re just validating your FTP details.',
                );
            }

            /**
             * Enable the button if validation has failed
             */
            if (!validateFields) {
                $(this).prop('disabled', false);
            }
        });

        /**
         * Validate the correct directory
         * @todo Cleanup to mimic new search directories
         */
        transferito.utilities.selector.on('click', '.transferito__directory-selection-validation', function() {
            /**
             * Disable the Button to stop double checks
             */
            $(this).prop('disabled', true);

            /**
             *
             */
            transferito.screenRouting(
                'databaseAuthentication',
                'Please wait...',
                'While we load the database details screen'
            );

        });

        /**
         * Validate the database details
         */
        transferito.utilities.selector.on('click', '.transferito__start-manual-migration', function() {
            /**
             * Disable the Button to stop double checks
             */
            $(this).prop('disabled', true);

            /**
             * Validate that all required form fields have been completed
             */
            var validateFields = transferito.utilities.validateFormFields();

            /**
             * If validation has passed
             */
            if (validateFields) {
                /**
                 * Get the manual server details information
                 */
                var databaseDetail = transferito.utilities.buildPayload();

                /**
                 * Get the wpNonce
                 */
                var securityKey = $('#manualMigrationDatabaseDetail').val();

                /**
                 * Start the validation
                 */
                transferito.databaseDetailValidation(
                    securityKey,
                    databaseDetail,
                    'Please wait..',
                    'We\'re just validating your database details'
                );
            }

            /**
             * Re enable the button
             */
            if (!validateFields) {
                $(this).prop('disabled', false);
            }

        });

        /**
         * Return to ftp screen
         */
        transferito.utilities.selector.on('click', '.transferito__edit-directory', function() {
            /**
             * Disable the Button to stop double checks
             */
            $(this).prop('disabled', true);

            /**
             *
             */
            transferito.loadDirectoryTemplate();
        });

        /**
         * Close the modal by clicking on the background
         */
        transferito.utilities.selector.on('click', '.transferito__modal', function(event) {

            var parentClassList = $(event.target).parents();

            /**
             * Only close the modal if the class list does not include the inner modal class
             */
            if (!parentClassList.hasClass('transferito__modal__inner')) {
                $(this).closest('.transferito__modal').addClass('transferito__modal--hide');
            }
        });

        /**
         * Toggle cPanel API Token
         */
        transferito.utilities.selector.on('change', '.show-cpanel-password', function() {
            $('#cpanelPasswordElement').toggleClass('transferito__hide-element');
            $('#cpanelAPITokenElement').toggleClass('transferito__hide-element');
            $('#cpanelAuth').prop('disabled', !transferito.utilities.validateFormFields());
        });

        /**
         * Close the quickstart
         */
        transferito.utilities.selector.on('click', '.transferito__modal--hide-quickstart', function() {
            $(this).closest('.transferito__modal--without').addClass('transferito__modal--hide');
            transferito.hideQuickStartPopup();
        });

        /**
         * Select the migration method
         * Switch the pro-tip column at the same time
         */
        transferito.utilities.selector.on('click', '.transferito-migration-method__selection-method', function() {
            var selector = $(this);
            var migrationMethod = selector.data('selectMigrationMethod');
            var selectedClass = 'transferito-migration-method__selection-method--selected';
            var hideProTip = 'transferito__pro-tip-column--hide';
            var selectedProTipID = migrationMethod === 'cpanel' ? 'cPanelProTip' : 'FTPProTip';

            $('.transferito-migration-method__selection-method').removeClass(selectedClass);
            $('.transferito__pro-tip-column').addClass(hideProTip);
            selector.addClass(selectedClass);
            $('#' + selectedProTipID).removeClass(hideProTip);
        });

        /**
         * Switch to the correct migration method
         */
        transferito.utilities.selector.on('click', '#selectMigrationMethod', function() {
            var migrationMethod = $('.transferito-migration-method__selection-method--selected').data('selectMigrationMethod');
            transferito.switchMode(migrationMethod, 'Please wait...', 'We\'re just preparing your migration method');
        });

        /**
         * Route the screen back to a desired screen
         */
        transferito.utilities.selector.on('click', '.transferito__screen-routing', function() {
            transferito.screenRouting(
                $(this).data('screenRoute'),
                'Please wait...',
                'While we redirect you to the correct screen'
            );
        });

        /**
         * Validate the cPanel auth screen form fields - by checking at least two have been completed
         */
        transferito.utilities.selector.on('change keyup paste', '.transferito-cpanel-authentication__input input', function() {
            setTimeout(function(){
                $('#cpanelAuth').prop('disabled', !transferito.utilities.validateFormFields());
            },0);
        });

        /**
         * Validate the ftp auth screen form fields - by checking at least two have been completed
         */
        transferito.utilities.selector.on('change keyup paste', '.transferito-ftp-authentication__input input', function() {
            setTimeout(function(){
                $('#manualServerDetails').prop('disabled', !transferito.utilities.validateFormFields());
            },0);
        });

        /**
         * Validate the database auth screen form fields - by checking at least two have been completed
         */
        transferito.utilities.selector.on('change keyup paste', '.transferito-database-authentication__input input', function() {
            setTimeout(function(){
                $('#manualServerMigrationStart').prop('disabled', !transferito.utilities.validateFormFields());
            },0);
        });

        /**
         * Validate the hosting form fields - by checking at least two have been completed
         */
        transferito.utilities.modalSelector.on('change keyup transferito__field-required', '.transferito-information__form-field input', function() {
            $('#fireRequestHostingGuide').prop('disabled', !transferito.utilities.validateFormFields());
        });

        /**
         * Switch the information instructions based on the dropdown result
         */
        transferito.utilities.modalSelector.on('change', '#selectHostingProvider', function() {
            var guideID = 'guideFor_' + $(this).val();
            var guideName = $('#tutorialName').val();
            var videoID = $(this).find(':selected').data('guideVideo');

            $('.transferito-information__steps-list').each(function() {
                var currentID = $(this).attr('id');

                if (currentID === guideID) {
                    $(this).removeClass('transferito__hide-element');
                } else if (guideID === 'guideFor_not-listed') {
                    transferito.utilities.displayFormGuideModal('requestHostingGuideForm', guideName);
                } else if (currentID !== guideID) {
                    $(this).addClass('transferito__hide-element');
                }
            });

            /**
             * If the option contains a videoID
             * Replace current video with the new videoID
             */
            if (videoID) {
                var videoURL = 'https://www.youtube.com/embed/' + videoID + '?autoplay=1&fs=1&rel=0';
                var videoIframe = $('.transferito-information__video iframe:visible');
                videoIframe.attr('src', videoURL);
            }
        });

        /**
         * Process sending the guide details
         */
        transferito.utilities.modalSelector.on('click', '.transferito__request-hosting-guide', function() {
            /**
             * Disable the Button to stop double checks
             */
            $(this).prop('disabled', true);

            /**
             * Validate that all required form fields have been completed
             */
            var validateFields = transferito.utilities.validateFormFields();

            /**
             * If validation has passed
             */
            if (validateFields) {
                /**
                 * Get the manual server details information
                 */
                var hostingGuideDetail = {
                    securityKey: $('#hostingGuideDetails').val(),
                    guideName: $('#hostingGuideName').val(),
                    hostingProvider: $('#field__hostingProvider').val(),
                    emailAddress: $('#field__emailAddress').val()
                };

                transferito.sendGuideRequestForm(
                    hostingGuideDetail,
                    'Please wait...',
                    'We\'re just sending your request'
                );
            }

            /**
             * Re-enable the button
             */
            if (!validateFields) {
                $(this).prop('disabled', false);
            }
        });

        /**
         * Log Event on external links clicked
         */
        $('#wpwrap').on('click', '.transferito-log-event', function() {
            transferito.utilities.logEvent('externalLinkClicked', {
                destination: $(this).data('eventName')
            });
        });

        /**
         * When the Destination URL is pasted or selected via input autofill dropdown
         * If the Destination URL has a protocol - Strip it and select the domain protocol
         */
        transferito.utilities.selector.on('paste change', '#domain', function(event) {
            var target = $(event.target);

            setTimeout(function(){
                var pastedContent = target.val();
                var splitContent = pastedContent.trim().split('://');

                /**
                 * Validate the correct protocol will be selected
                 */
                if (splitContent.length === 2) {
                    var allowedProtocols = ['http://', 'https://'];
                    var defaultProtocol = allowedProtocols[0];
                    var protocol = splitContent[0] + '://';
                    var domain = splitContent[1];
                    var isUserSpecifiedProtocolAllowed = allowedProtocols.includes(protocol);
                    var validatedProtocol = isUserSpecifiedProtocolAllowed ? protocol : defaultProtocol;

                    $('#domain').val(domain);
                    $('#domainProtocol').val(validatedProtocol);

                    $('#cpanelCheck').prop('disabled', false);
                }
            },0);

        });


    });

})(jQuery);
