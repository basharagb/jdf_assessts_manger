window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-month-picker]').forEach(picker => {
        const input = picker.querySelector('input[type="hidden"]');
        const toggle = picker.querySelector('.month-picker-toggle');
        const display = picker.querySelector('[data-month-display]');
        const popover = picker.querySelector('.month-picker-popover');
        const yearSelect = picker.querySelector('[data-year-select]');
        const [minYear, minMonth] = picker.dataset.min.split('-').map(Number);
        const [maxYear, maxMonth] = picker.dataset.max.split('-').map(Number);
        const formatLabel = value => {
            if (!value) return 'اختر الشهر';
            const [year, month] = value.split('-').map(Number);
            const label = new Intl.DateTimeFormat('ar-JO', { month: 'long', timeZone: 'UTC' })
                .format(new Date(Date.UTC(year, month - 1, 1)));
            return `${label} ${year}`;
        };
        const refreshMonths = () => {
            const year = Number(yearSelect.value);
            picker.querySelectorAll('[data-month]').forEach(button => {
                const month = Number(button.dataset.month);
                button.disabled = year < minYear || year > maxYear || (year === minYear && month < minMonth) || (year === maxYear && month > maxMonth);
                button.classList.toggle('is-selected', input.value === `${year}-${String(month).padStart(2, '0')}`);
            });
            picker.querySelector('[data-year-step="-1"]').disabled = yearSelect.selectedIndex <= 0;
            picker.querySelector('[data-year-step="1"]').disabled = yearSelect.selectedIndex >= yearSelect.options.length - 1;
        };
        const close = () => {
            popover.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        display.textContent = formatLabel(input.value);
        refreshMonths();
        toggle.addEventListener('click', () => {
            const opening = popover.hidden;
            document.querySelectorAll('.month-picker-popover').forEach(panel => {
                panel.hidden = true;
                panel.closest('[data-month-picker]')?.querySelector('.month-picker-toggle')?.setAttribute('aria-expanded', 'false');
            });
            popover.hidden = !opening;
            toggle.setAttribute('aria-expanded', String(opening));
        });
        yearSelect.addEventListener('change', refreshMonths);
        picker.querySelectorAll('[data-year-step]').forEach(button => button.addEventListener('click', () => {
            yearSelect.selectedIndex += Number(button.dataset.yearStep);
            yearSelect.dispatchEvent(new Event('change'));
        }));
        picker.querySelectorAll('[data-month]').forEach(button => button.addEventListener('click', () => {
            input.value = `${yearSelect.value}-${button.dataset.month}`;
            display.textContent = formatLabel(input.value);
            close();
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            refreshMonths();
        }));
        picker.querySelector('[data-clear-month]')?.addEventListener('click', () => {
            input.value = '';
            display.textContent = formatLabel('');
            close();
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            refreshMonths();
        });
        document.addEventListener('click', event => {
            if (!picker.contains(event.target)) close();
        });
        picker.addEventListener('keydown', event => {
            if (event.key === 'Escape') close();
        });
    });
});
