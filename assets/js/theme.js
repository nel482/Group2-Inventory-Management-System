// Theme Management
const ThemeManager = {
    LIGHT_MODE: 'light',
    DARK_MODE: 'dark',
    STORAGE_KEY: 'asaj_theme',

    /**
     * Initialize theme from localStorage or system preference
     */
    init() {
        const savedTheme = localStorage.getItem(this.STORAGE_KEY);

        if (savedTheme) {
            this.setTheme(savedTheme);
        } else {
            // Check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.setTheme(prefersDark ? this.DARK_MODE : this.LIGHT_MODE);
        }

        this.setupToggleButton();
    },

    /**
     * Set theme and save to localStorage
     */
    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(this.STORAGE_KEY, theme);
        this.updateToggleButton(theme);
    },

    /**
     * Toggle between light and dark mode
     */
    toggle() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || this.LIGHT_MODE;
        const newTheme = currentTheme === this.LIGHT_MODE ? this.DARK_MODE : this.LIGHT_MODE;
        this.setTheme(newTheme);
    },

    /**
     * Create and setup the toggle button
     */
    setupToggleButton() {
        // Remove existing button if present
        const existing = document.querySelector('.theme-toggle');
        if (existing) {
            existing.remove();
        }

        const button = document.createElement('button');
        button.className = 'theme-toggle';
        button.setAttribute('aria-label', 'Toggle theme');
        button.addEventListener('click', () => this.toggle());
        document.body.appendChild(button);

        const currentTheme = document.documentElement.getAttribute('data-theme');
        this.updateToggleButton(currentTheme);
    },

    /**
     * Update toggle button class based on current theme
     */
    updateToggleButton(theme) {
        const button = document.querySelector('.theme-toggle');
        if (button) {
            button.className = 'theme-toggle ' + (theme === this.LIGHT_MODE ? 'light-mode' : 'dark-mode');
        }
    }
};

// Initialize theme when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ThemeManager.init());
} else {
    ThemeManager.init();
}

/**
 * SweetAlert Theme Helper - Provides theme-aware configuration
 */
const SweetAlertTheme = {
    /**
     * Get current theme
     */
    getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    },

    /**
     * Get CSS variable value
     */
    getCSSVariable(varName) {
        return getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
    },

    /**
     * Get theme-aware configuration
     */
    getConfig() {
        const isDark = this.getCurrentTheme() === 'dark';
        
        return {
            background: isDark ? this.getCSSVariable('--card-bg') : '#ffffff',
            color: isDark ? this.getCSSVariable('--text-primary') : '#2a2a2a',
            confirmButtonColor: isDark ? '#64b5f6' : '#0066cc',
            cancelButtonColor: isDark ? '#666' : '#ccc',
            inputBg: isDark ? this.getCSSVariable('--input-bg') : '#ffffff',
            inputBorder: isDark ? this.getCSSVariable('--input-border') : '#c0c0c0',
            inputText: isDark ? this.getCSSVariable('--input-text') : '#2a2a2a',
            labelColor: isDark ? 'rgba(232, 232, 232, 0.8)' : 'rgba(42, 42, 42, 0.8)'
        };
    },

    /**
     * Get input HTML with theme-aware styles
     */
    getInputHTML(fields) {
        const config = this.getConfig();
        let html = '<div style="text-align: left;">';
        
        for (const field of fields) {
            const style = `width: 100%; padding: 8px; background: ${config.inputBg}; border: 1px solid ${config.inputBorder}; border-radius: 4px; color: ${config.inputText};`;
            html += `<div style="margin: 12px 0;">
                <label style="display: block; color: ${config.labelColor}; font-size: 12px; margin-bottom: 6px;">${field.label}</label>
                <input type="${field.type || 'text'}" id="${field.id}" placeholder="${field.placeholder}" value="${field.value || ''}" ${field.attributes || ''} style="${style}">
            </div>`;
        }
        
        html += '</div>';
        return html;
    },

    /**
     * Fire alert with theme
     */
    fire(options = {}) {
        const config = this.getConfig();
        const mergedConfig = {
            background: config.background,
            color: config.color,
            confirmButtonColor: options.confirmButtonColor || config.confirmButtonColor,
            ...options
        };
        
        return Swal.fire(mergedConfig);
    }
};
