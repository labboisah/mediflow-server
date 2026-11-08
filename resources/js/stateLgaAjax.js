document.addEventListener('DOMContentLoaded', function () {

    $('select[name="state"]').on('change', function () {

        let state_id = $(this).val();

        if(state_id){

            $.ajax({
                url: '/ajax/state/' + state_id + '/get-lgas',
                type: 'GET',
                dataType: 'json',

                success: function(data){

                    $('select[name="lga"]').empty();

                    $.each(data, function(key, value){

                        $('select[name="lga"]').append(
                            '<option value="' + key + '">' +
                            value +
                            '</option>'
                        );

                    });

                }
            });

        } else {

            $('select[name="lga"]').empty();

        }

    });

});