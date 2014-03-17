if (jQuery) {
    (function($){
        $(document).ready(function(){
            $('input.tx-twlucenesearch-sword').each(function() {
                var input = $(this);
                var defaultValue = $.trim(input.attr('title'));
                input.attr('title', '');

                if (defaultValue) {
                    if (placeholderIsSupported()) {
                        input.attr('placeholder', defaultValue);
                        if ($.trim(input.val()) === defaultValue) {
                            input.val('');
                        }
                    } else {
                        input.focus(function(){
                            if ($.trim(input.val()) === defaultValue) {
                                input.val('');
                                input.removeClass('default');
                            }
                        }).blur(function(){
                            if (!$.trim(input.val())) {
                                input.val(defaultValue);
                                input.addClass('default');
                            }
                        }).trigger('blur');

                        $(input.closest('form')).submit(function(){
                            input.trigger('focus');
                        });
                    }
                }
            });
        });

        function placeholderIsSupported() {
            return ('placeholder' in document.createElement('input'));
        }
    })(jQuery);
}