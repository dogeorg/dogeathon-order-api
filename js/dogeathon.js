$(document).ready(function () {
    $('#wf-form-Register-2').on('submit', function (event) {
        event.preventDefault(); // Stop default form submission

        if (this.reportValidity()) {
            const dogecoinAdd = $('#doge-address').val();

            if (!bs58caddr.validateCoinAddress('DOGE', dogecoinAdd)) {
                alert('Sorry Shibe, Doge Address is not valid!');
                return;
            }

            // Proceed with AJAX submission
            sendtoGigaWallet();
        }
    });

    async function sendtoGigaWallet() {
        const formData = {
            name: $('#name').val(),
            email: $('#email-address').val(),
            country: $('#country').val(),
            github: $('#github-username').val(),            
            x: $('#x-username').val(),
            dogeAddress: $('#doge-address').val(),
        };

        $.ajax({
            url: 'inc/vendors/gigawallet-api.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function (response) {
                if (response && response.GigaQR && response.PaytoDogeAddress) {
                    // Hide form and show success message
                    $('#attend-form').hide();
                    $('<div class="registration-success">Much Registration Success!</div>').insertBefore('#modal-vote-success');

                    // Update QR code and Doge address in modal
                    $('#modal-vote-success .qr-code').attr('src', response.GigaQR);
                    $('#payment-address').val(response.PaytoDogeAddress);

                    // Show the modal
                    $('#modal-vote-success').fadeIn();
                } else {
                    alert('Sorry shibe, there was a problem with the response, try again!');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error:', textStatus, errorThrown);
                alert('Sorry shibe, there was a problem, try again!');
            }
        });
    }

    // Optional: Modal close logic
    $('.modal-close').on('click', function (e) {
        e.preventDefault();
        $('#modal-vote-success').fadeOut();
    });
});
