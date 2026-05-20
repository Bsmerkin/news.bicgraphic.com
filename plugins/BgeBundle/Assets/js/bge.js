Mautic.internalEmailBatchSubmit = function() {
    if (Mautic.batchActionPrecheck()) {
        var emailValue = mQuery('#internalemail_sendbatch_email').val();

        // Check if the email select element has a selected option with a value
        if (emailValue && emailValue !== 'Select an email') {
            var ids = Mautic.getCheckedListIds(false, true);

            // Ensure the hidden input exists and is updated
            if (mQuery('#internalemail_sendbatch_ids').length) {
                mQuery('#internalemail_sendbatch_ids').val(ids); // Pass IDs as JSON
            }

            return true;
        }
    }

    mQuery('#MauticSharedModal').modal('hide');

    return false;
};