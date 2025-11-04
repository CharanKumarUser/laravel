$(document).ready(function() {
    $('#agreeTerms').change(function() {
        if ($(this).is(':checked')) {
            $('#payNowBtn').prop('disabled', false);
        } else {
            $('#payNowBtn').prop('disabled', true);
        }
    });
}); 