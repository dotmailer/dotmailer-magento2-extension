/**
 * Dotdigital tracking proxy for Magento
 * Queues method calls until the real Dotdigital script loads
 *
 * @fileoverview Provides a proxy interface for Dotdigital tracking that queues
 * method calls until the external Dotdigital script is loaded and ready.
 * @author Dotdigitalgroup
 * @version 1.0.0
 */
const queuedMethodCalls = [];

/**
 * Initialize Dotdigital tracking and load the external script
 *
 * @param {string} regionPrefix - Dotdigital region identifier (e.g., 'r1', 'r2', 'r3')
 * @param {string} profileId - Dotdigital profile/account identifier
 * @returns {void}
 */
const initializeDotdigital = (regionPrefix, profileId) => {
    if (!regionPrefix || !profileId) {
        console.warn('Dotdigital initialization failed: regionId and profileId are required');
        return;
    }

    if (document.querySelector(`script[src*="${profileId}/ddgtag.js"]`)) {
        triggerReadyEvent();
        return;
    }

    const script = document.createElement('script');
    script.src = `//${regionPrefix}.ddlnk.net/${profileId}/ddgtag.js`;
    script.async = true;
    script.onerror = () => console.error('Failed to load Dotdigital script');
    document.head.appendChild(script);
};

/**
 * Mark Dotdigital as ready and process all queued method calls
 *
 * @returns {void}
 */
const triggerReadyEvent = () => {
    if ('isProxy' in window.ddg) return;
    window.dispatchEvent(new CustomEvent('DotdigitalTagLoaded', { detail: { timestamp: Date.now() } }));
    processQueuedMethodCalls();
};

/**
 * Execute all queued method calls using the current Dotdigital object
 *
 * @returns {void}
 */
const processQueuedMethodCalls = () => {
    while (queuedMethodCalls.length > 0) {
        const { methodName, methodArguments, promiseResolve } = queuedMethodCalls.shift();
        if (window.ddg && typeof window.ddg[methodName] === 'function') {
            try {
                const result = window.ddg[methodName](...methodArguments);
                promiseResolve?.(result);
            } catch (error) {
                console.error(`Error calling Dotdigital ${methodName}:`, error);
                promiseResolve?.(false);
            }
        } else {
            console.log(`Dotdigital ${methodName}:`, methodArguments);
            promiseResolve?.(true);
        }
    }
};

/**
 * Create a proxy object that intercepts and queues Dotdigital method calls
 *
 * @returns {Proxy} The proxy object for Dotdigital tracking
 */
const createDotdigitalProxy = () => new Proxy(
    { init: initializeDotdigital, isProxy: true },
    {
    /**
     * Proxy getter that intercepts method calls
     *
     * @param {Object} target - The target object
     * @param {string} methodName - The method name being accessed
     * @returns {Function} The intercepted method or queuing function
     */
    get(target, methodName) {
        if (methodName in target) {
            return target[methodName];
        }
        return (...args) => {
            return new Promise(resolve => {
                queuedMethodCalls.push({
                    methodName,
                    methodArguments: args,
                    promiseResolve: resolve
                });
            });
        };
    }
});

/**
 * Override window.ddg setter to detect object replacement
 */
Object.defineProperty(window, 'ddg', {
    get: () => window._ddgCurrent,
    set: (value)  => (window._ddgCurrent = value, triggerReadyEvent())
});

// Initialize with our proxy
window._ddgCurrent = createDotdigitalProxy();
