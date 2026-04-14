import { Submit } from "./Forms.js";

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('VerificationRequestForm');
    if (!form) return;

    const reasonTextarea = document.getElementById('VerifReason');
    const feeCheckbox = document.getElementById('FeeConfirmed');
    const submitBtn = document.getElementById('VerifSubmitBtn');
    const charCounter = document.getElementById('VerifCharCounter');
    const formResponse = document.getElementById('VerifFormResponse');

    function UpdateSubmitState() {
        const hasReason = reasonTextarea.value.trim().length >= 20;
        const hasFee = feeCheckbox.checked;
        submitBtn.disabled = !(hasReason && hasFee);
    }

    reasonTextarea.addEventListener('input', () => {
        charCounter.textContent = reasonTextarea.value.length;
        UpdateSubmitState();
    });

    feeCheckbox.addEventListener('change', UpdateSubmitState);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        formResponse.className = '';
        formResponse.textContent = '';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        const formData = new FormData();
        formData.append('ReqType', 1);
        formData.append('reason', reasonTextarea.value.trim());
        formData.append('fee_confirmed', feeCheckbox.checked ? 1 : 0);
        const pageIDInput = form.querySelector('input[name="PageID"]');
        if (pageIDInput) formData.append('PageID', pageIDInput.value);

        const res = await Submit('POST', 'Origin/Operations/Verification.php', formData);

        if (res.success) {
            formResponse.className = 'Success';
            formResponse.textContent = res.message;
            // Reload after short delay to show pending state
            setTimeout(() => window.location.reload(), 1800);
        } else {
            formResponse.className = 'Error';
            formResponse.textContent = res.message || 'An unexpected error occurred.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Request';
        }
    });
});
