/**
 * InvestEdu - Modal Management
 * Handles modal creation, display, and interaction
 */

const Modals = {
    /**
     * Create and show modal
     * @param {Object} options - Modal options
     * @returns {HTMLElement} Modal element
     */
    show(options = {}) {
        const {
            title = '',
            content = '',
            size = 'md', // sm, md, lg, xl
            showCloseButton = true,
            closeOnBackdrop = true,
            buttons = []
        } = options;

        // Remove existing modal if any
        this.close();

        // Create modal overlay
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
        overlay.id = 'modal-overlay';

        // Create modal container
        const modal = document.createElement('div');
        modal.className = `modal bg-white rounded-2xl shadow-xl max-w-${size === 'sm' ? 'sm' : size === 'lg' ? '2xl' : size === 'xl' ? '4xl' : 'lg'} w-full max-h-[90vh] overflow-y-auto`;
        modal.id = 'modal';

        // Modal header
        if (title) {
            const header = document.createElement('div');
            header.className = 'modal-header p-6 border-b border-gray-200 flex items-center justify-between';
            
            const titleElement = document.createElement('h2');
            titleElement.className = 'text-xl font-bold text-slate-900';
            titleElement.textContent = title;
            
            header.appendChild(titleElement);
            
            if (showCloseButton) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'text-gray-400 hover:text-gray-600 transition';
                closeBtn.innerHTML = '<i class="fa-solid fa-times"></i>';
                closeBtn.onclick = () => this.close();
                header.appendChild(closeBtn);
            }
            
            modal.appendChild(header);
        }

        // Modal body
        const body = document.createElement('div');
        body.className = 'modal-body p-6';
        
        if (typeof content === 'string') {
            body.innerHTML = content;
        } else {
            body.appendChild(content);
        }
        
        modal.appendChild(body);

        // Modal footer
        if (buttons.length > 0) {
            const footer = document.createElement('div');
            footer.className = 'modal-footer p-6 border-t border-gray-200 flex justify-end gap-3';
            
            buttons.forEach(button => {
                const btn = document.createElement('button');
                btn.className = button.className || 'px-4 py-2 rounded-lg font-medium transition';
                btn.textContent = button.text;
                btn.onclick = () => {
                    if (button.onClick) {
                        button.onClick();
                    }
                    if (button.close !== false) {
                        this.close();
                    }
                };
                footer.appendChild(btn);
            });
            
            modal.appendChild(footer);
        }

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Close on backdrop click
        if (closeOnBackdrop) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.close();
                }
            });
        }

        // Close on Escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.close();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);

        // Animate in
        setTimeout(() => {
            overlay.classList.add('fade-in');
            modal.classList.add('fade-in');
        }, 10);

        return modal;
    },

    /**
     * Close modal
     */
    close() {
        const overlay = document.getElementById('modal-overlay');
        if (overlay) {
            overlay.remove();
        }
    },

    /**
     * Show confirmation modal
     * @param {Object} options - Confirmation options
     * @returns {Promise<boolean>} User's choice
     */
    confirm(options = {}) {
        return new Promise((resolve) => {
            const {
                title = 'Confirm Action',
                message = 'Are you sure you want to proceed?',
                confirmText = 'Confirm',
                cancelText = 'Cancel',
                confirmClass = 'bg-red-600 text-white hover:bg-red-700'
            } = options;

            this.show({
                title,
                content: `<p class="text-slate-600">${message}</p>`,
                buttons: [
                    {
                        text: cancelText,
                        className: 'px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition',
                        onClick: () => resolve(false),
                        close: true
                    },
                    {
                        text: confirmText,
                        className: `px-4 py-2 ${confirmClass} rounded-lg font-medium transition`,
                        onClick: () => resolve(true),
                        close: true
                    }
                ]
            });
        });
    },

    /**
     * Show alert modal
     * @param {Object} options - Alert options
     */
    alert(options = {}) {
        const {
            title = 'Alert',
            message = '',
            type = 'info', // info, success, warning, error
            buttonText = 'OK'
        } = options;

        const typeClasses = {
            info: 'text-blue-600',
            success: 'text-green-600',
            warning: 'text-amber-600',
            error: 'text-red-600'
        };

        this.show({
            title,
            content: `<p class="${typeClasses[type]}">${message}</p>`,
            buttons: [
                {
                    text: buttonText,
                    className: 'px-4 py-2 bg-slate-800 text-white rounded-lg font-medium hover:bg-slate-700 transition',
                    close: true
                }
            ]
        });
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Modals;
}
