class ErrorHandler {
    constructor() {
        this.listeners = [];
    }

    // Global error tracking
    track(error, context = {}) {
        console.error('[Global Error]', error, context);

        // Optional: Send to error tracking service like Sentry
        if (window.Sentry) {
            window.Sentry.captureException(error, { extra: context });
        }

        // Notify registered listeners
        this.notifyListeners(error, context);
    }

    // Retry mechanism for async operations
    async withRetry(fn, options = {}) {
        const {
            maxRetries = 3,
            delay = 1000,
            onRetry = () => {}
        } = options;

        let lastError = null;
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                return await fn();
            } catch (error) {
                lastError = error;
                onRetry(attempt, error);

                // Exponential backoff
                await new Promise(resolve => 
                    setTimeout(resolve, delay * Math.pow(2, attempt))
                );
            }
        }

        this.track(lastError, { 
            context: 'Retry Exhausted', 
            attempts: maxRetries 
        });

        throw lastError;
    }

    // Event-based error handling
    addListener(listener) {
        this.listeners.push(listener);
        return () => {
            // Return unsubscribe function
            this.listeners = this.listeners.filter(l => l !== listener);
        };
    }

    notifyListeners(error, context) {
        this.listeners.forEach(listener => 
            listener(error, context)
        );
    }

    // Network error handling
    isNetworkError(error) {
        return error.message === 'Network Error' || 
               (error.response && error.response.status >= 500);
    }

    // Validation error handling
    isValidationError(error) {
        return error.response && error.response.status === 422;
    }

    // Specific error type handlers
    handleNetworkError(error, fallbackMessage = 'Network error. Please check your connection.') {
        this.track(error, { type: 'network' });
        return fallbackMessage;
    }

    handleValidationError(error) {
        const errors = error.response?.data?.errors || {};
        const errorMessages = Object.values(errors).flat();
        
        this.track(error, { 
            type: 'validation', 
            errors: errorMessages 
        });

        return errorMessages[0] || 'Validation failed';
    }
}

export default new ErrorHandler();