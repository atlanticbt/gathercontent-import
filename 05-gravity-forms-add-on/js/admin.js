;(function($){
    $(document).ready(function() {
        $('.ajax-get-gc-accounts').on('click', function(e) {
            var $t = $(this);
            e.preventDefault();
            var data = {
                '_wpnonce': $('#gf_gathercontent_nonce').val(),
                'action': 'gravity_gc_get_accounts',
                'email': $('#gravity_gc_email').val(),
                'apikey': $('#gravity_gc_apikey').val()
            };

            $.ajax({
                url: ajaxurl,
                data: data,
                dataType: 'json',
                type: 'POST',
                error: function() {

                },
                success: function(response) {
                    if(typeof response.success !== 'undefined') {
                        if(response.success) {
                            $t.parent().html(response.html);
                        }
                        else {
                            $t.before(response.html);
                        }
                    }
                }
            });
        });


        $('#gravity_gc_form, #gravity_gc_project, #gravity_gc_template').on('change', function(e) {
            var $val = $(this).val();
            if($val !== -1) {
                window.location.href = $val;
            }
        });


        $('#gravity_gc_title_fields').on('change', function(e) {
            var $val = $(this).val();

            $('.gc_row input[name^="gravity_gc[title][lengths]"]').closest('.gc_row').hide();

            if($val) {
                for(var i=0,il = $val.length; i<il; i++) {
                    $('.gc_row input[name="gravity_gc[title][lengths][' + $val[i] + ']"]').closest('.gc_row').show();
                }
            }
        }).trigger('change');
    });
})(jQuery);
