        </div>
    </div>
    
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * Shows loading state on a button without changing its design
         * @param {jQuery|string} button - Button element or selector
         * @param {string} loadingText - Optional text to show while loading
         */
        function setButtonLoading(button, loadingText = '') {
            const $btn = $(button);
            if ($btn.length === 0 || $btn.data('loading-active')) return;
            
            if (!$btn.data('original-html')) {
                $btn.data('original-html', $btn.html());
            }
            if (!$btn.data('original-disabled')) {
                $btn.data('original-disabled', $btn.prop('disabled'));
            }
            
            $btn.data('loading-active', true);
            $btn.prop('disabled', true);
            
            const $spinner = $('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');
            const originalContent = $btn.contents();
            
            if (originalContent.length > 0) {
                $btn.prepend($spinner);
            } else {
                $btn.html($spinner[0].outerHTML + (loadingText || 'Loading...'));
            }
        }
        
        /**
         * Removes loading state from a button and restores original design
         * @param {jQuery|string} button - Button element or selector
         */
        function removeButtonLoading(button) {
            const $btn = $(button);
            if ($btn.length === 0 || !$btn.data('loading-active')) return;
            
            const originalHtml = $btn.data('original-html');
            const originalDisabled = $btn.data('original-disabled');
            
            if (originalHtml) {
                $btn.html(originalHtml);
                $btn.removeData('original-html');
            }
            
            if (originalDisabled !== undefined) {
                $btn.prop('disabled', originalDisabled);
                $btn.removeData('original-disabled');
            } else {
                $btn.prop('disabled', false);
            }
            
            $btn.removeData('loading-active');
        }
        
        /**
         * Wraps AJAX calls with button loading state
         * @param {jQuery|string} button - Button element or selector
         * @param {Function} ajaxCall - Function that returns jQuery AJAX promise
         * @param {string} loadingText - Optional loading text
         */
        function ajaxWithLoading(button, ajaxCall, loadingText = 'Loading...') {
            setButtonLoading(button, loadingText);
            
            const promise = ajaxCall();
            
            promise.always(function() {
                removeButtonLoading(button);
            });
            
            return promise;
        }
        
        $(document).ready(function() {
            $('form').on('submit', function(e) {
                const $form = $(this);
                const $submitBtn = $form.find('button[type="submit"], input[type="submit"]');
                
                if ($submitBtn.length > 0 && !$form.data('no-loading')) {
                    setButtonLoading($submitBtn);
                }
            });
            
            $('[data-loading]').on('click', function() {
                const $btn = $(this);
                setButtonLoading($btn);
            });
            function setupAlertAutoDismiss($alert) {
                if ($alert.data('auto-dismiss-setup')) {
                    return;
                }
                
                if ($alert.data('no-auto-dismiss') === true) {
                    return;
                }
                
                const isError = $alert.hasClass('alert-danger');
                const isWarning = $alert.hasClass('alert-warning');
                const timer = isError ? 7000 : (isWarning ? 6000 : 10000);
                
                $alert.css('position', 'relative');
                
                const $progressBar = $('<div class="alert-progress"></div>');
                $alert.append($progressBar);
                
                $progressBar.css({
                    'width': '100%',
                    'transition': 'width ' + (timer / 1000) + 's linear',
                    'height': '3px',
                    'background': 'rgba(0,0,0,0.2)',
                    'position': 'absolute',
                    'bottom': '0',
                    'left': '0',
                    'border-radius': '0 0 var(--radius-md) var(--radius-md)',
                    'z-index': '1'
                });
                
                setTimeout(function() {
                    $progressBar.css('width', '0%');
                }, 10);
                
                setTimeout(function() {
                    if ($alert.length && $alert.is(':visible')) {
                        const bsAlert = new bootstrap.Alert($alert[0]);
                        bsAlert.close();
                    }
                }, timer);
                
                $alert.data('auto-dismiss-setup', true);
            }
            
            $('.alert').each(function() {
                setupAlertAutoDismiss($(this));
            });
            
            const alertObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            if ($(node).hasClass('alert')) {
                                setupAlertAutoDismiss($(node));
                            }
                            $(node).find('.alert').each(function() {
                                setupAlertAutoDismiss($(this));
                            });
                        }
                    });
                });
            });
            
            alertObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    </script>
    
    <?php if (isset($additionalJS)) echo $additionalJS; ?>
</body>
</html>


