import { getCurrentInstance } from 'vue';
import { VueReCaptcha } from 'vue-recaptcha-v3';

// Global flag to track if reCAPTCHA has been installed
let isRecaptchaInstalled = false;

export function useRecaptchaPlugin() {
    const instance = getCurrentInstance();
    
    if (!instance) {
        throw new Error('useRecaptchaPlugin must be called within a Vue component');
    }
    
    const app = instance.appContext.app;
    
    // Only install the plugin once per application
    if (!isRecaptchaInstalled) {
        try {
            app.use(VueReCaptcha, { 
                siteKey: import.meta.env.VITE_RECAPTCHA_SITE_KEY,  
                loaderOptions: {
                    autoHideBadge: false,
                    badge: 'bottomright'
                }
            });
            isRecaptchaInstalled = true;
        } catch (error) {
            // If plugin is already installed, this will throw an error
            // We can safely ignore this error
            if (error.message.includes('already been applied')) {
                isRecaptchaInstalled = true;
            } else {
                console.error('Failed to install reCAPTCHA plugin:', error);
                throw error;
            }
        }
    }
    
    return {
        isInstalled: isRecaptchaInstalled
    };
} 