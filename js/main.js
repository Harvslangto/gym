function calculateAmount(isEditPage = false) {
    const typeSelect = document.getElementById('membership_type');
    const durationInput = document.getElementById('months');
    const durationLabel = document.getElementById('duration_label');
    const amountInput = document.getElementById('amount');
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');

    const selectedOption = typeSelect.options[typeSelect.selectedIndex];
    const price = parseFloat(selectedOption.getAttribute('data-price'));
    const isWalkIn = typeSelect.value.includes('Walk-in');

    if (isWalkIn) {
        durationLabel.innerText = 'Days';
        if (!isEditPage) {
            durationInput.value = 1;
            durationInput.readOnly = true;
        }
    } else {
        durationLabel.innerText = 'Months';
        durationInput.readOnly = false;
    }

    const duration = parseInt(durationInput.value) || 1;
    amountInput.value = (price * duration).toFixed(2);

    if (startInput._flatpickr && startInput._flatpickr.selectedDates[0]) {
        let startDate = new Date(startInput._flatpickr.selectedDates[0]);
        let endDate = new Date(startDate);

        if (isWalkIn) {
            endDate.setDate(startDate.getDate() + duration - 1);
        } else {
            // Fixed 30 days per month
            endDate.setDate(startDate.getDate() + (duration * 30) - 1);
        }

        if (endInput && endInput._flatpickr) {
            endInput._flatpickr.setDate(endDate, true);
        }
    }
}
