/**
 * InvestEdu - Form Validation & Handling
 * Form validation and submission utilities
 */

const Forms = {
    /**
     * Validate form field
     * @param {HTMLElement} field - Form field element
     * @param {Object} rules - Validation rules
     * @returns {Object} Validation result
     */
    validateField(field, rules = {}) {
        const value = field.value.trim();
        const errors = [];

        // Required validation
        if (rules.required && !value) {
            errors.push(`${this.getFieldLabel(field)} is required`);
        }

        // Email validation
        if (rules.email && value && !Utils.isValidEmail(value)) {
            errors.push('Please enter a valid email address');
        }

        // Min length validation
        if (rules.minLength && value.length < rules.minLength) {
            errors.push(`${this.getFieldLabel(field)} must be at least ${rules.minLength} characters`);
        }

        // Max length validation
        if (rules.maxLength && value.length > rules.maxLength) {
            errors.push(`${this.getFieldLabel(field)} must be no more than ${rules.maxLength} characters`);
        }

        // Pattern validation
        if (rules.pattern && value && !rules.pattern.test(value)) {
            errors.push(rules.patternMessage || 'Invalid format');
        }

        // Custom validation
        if (rules.custom && value) {
            const customError = rules.custom(value);
            if (customError) {
                errors.push(customError);
            }
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    },

    /**
     * Get field label
     * @param {HTMLElement} field - Form field element
     * @returns {string} Field label
     */
    getFieldLabel(field) {
        const label = field.closest('.form-group')?.querySelector('label');
        return label ? label.textContent.replace('*', '').trim() : 'This field';
    },

    /**
     * Show field error
     * @param {HTMLElement} field - Form field element
     * @param {string} message - Error message
     */
    showFieldError(field, message) {
        // Remove existing error
        this.clearFieldError(field);

        // Add error class
        field.classList.add('error');
        
        // Create error message element
        const errorElement = document.createElement('p');
        errorElement.className = 'field-error text-red-600 text-xs mt-1';
        errorElement.textContent = message;
        
        // Insert after field
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    },

    /**
     * Clear field error
     * @param {HTMLElement} field - Form field element
     */
    clearFieldError(field) {
        field.classList.remove('error');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    },

    /**
     * Validate entire form
     * @param {HTMLElement} form - Form element
     * @param {Object} rules - Validation rules object
     * @returns {boolean} Is form valid
     */
    validateForm(form, rules = {}) {
        let isValid = true;
        const fields = form.querySelectorAll('input, select, textarea');

        fields.forEach(field => {
            const fieldName = field.name || field.id;
            const fieldRules = rules[fieldName] || {};

            const validation = this.validateField(field, fieldRules);

            if (!validation.isValid) {
                isValid = false;
                this.showFieldError(field, validation.errors[0]);
            } else {
                this.clearFieldError(field);
            }
        });

        return isValid;
    },

    /**
     * Get form data as object
     * @param {HTMLElement} form - Form element
     * @returns {Object} Form data object
     */
    getFormData(form) {
        const formData = new FormData(form);
        const data = {};

        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }

        return data;
    },

    /**
     * Set form field values
     * @param {HTMLElement} form - Form element
     * @param {Object} data - Data object
     */
    setFormData(form, data) {
        Object.keys(data).forEach(key => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = data[key];
                } else if (field.type === 'radio') {
                    const radio = form.querySelector(`[name="${key}"][value="${data[key]}"]`);
                    if (radio) radio.checked = true;
                } else {
                    field.value = data[key];
                }
            }
        });
    },

    /**
     * Reset form
     * @param {HTMLElement} form - Form element
     */
    resetForm(form) {
        form.reset();
        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(field => this.clearFieldError(field));
    },

    /**
     * Character counter for textarea
     * @param {HTMLElement} textarea - Textarea element
     * @param {HTMLElement} counter - Counter element
     * @param {number} maxLength - Maximum length
     */
    setupCharacterCounter(textarea, counter, maxLength) {
        const updateCounter = () => {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${textarea.value.length} / ${maxLength}`;
            
            if (remaining < 0) {
                counter.classList.add('text-red-600');
            } else if (remaining < 50) {
                counter.classList.add('text-amber-600');
                counter.classList.remove('text-red-600');
            } else {
                counter.classList.remove('text-red-600', 'text-amber-600');
            }
        };

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Forms;
}
