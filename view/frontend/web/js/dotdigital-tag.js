/**
 * Dotdigital tracking proxy for Magento
 * Queues tracking calls until the real Dotdigital script loads and is configured
 *
 * @fileoverview Provides a proxy interface for Dotdigital tracking that queues
 * method calls until the external Dotdigital script is loaded and configured.
 * @author Dotdigitalgroup
 * @version 1.0.0
 */
const queuedMethodCalls = [];
let isConfigured = false;

/**
 * Initialize Dotdigital tracking and load the external script
 *
 * @param {string} endpoint - Dotdigital tracking endpoint
 * @param {string} profileId - Dotdigital profile/account identifier
 * @returns {void}
 */
const initializeDotdigital = (endpoint, profileId) => {
    if (!endpoint || !profileId) {
        console.warn('Dotdigital initialization failed: endpoint and profileId are required');
        return;
    }

    if (document.querySelector(`script[src*="${profileId}/ddgtag.js"]`)) {
        triggerReadyEvent();
        return;
    }

    const script = document.createElement('script');
    script.src = `//${endpoint}/${profileId}/ddgtag.js`;
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
    if (!window._ddgCurrent || window._ddgCurrent.isProxy) return;
    window.dispatchEvent(new CustomEvent('DotdigitalTagLoaded', { detail: { timestamp: Date.now() } }));
    processQueuedMethodCalls();
};

/**
 * Execute eligible queued calls, configuring the tag before tracking
 *
 * @returns {void}
 */
const processQueuedMethodCalls = () => {
    if (!window._ddgCurrent || window._ddgCurrent.isProxy) return;

    while (queuedMethodCalls.length > 0) {
        // Configuration must overtake tracking calls queued earlier in the page.
        const configurationIndex = queuedMethodCalls.findIndex(call => call.methodName === 'configuration');
        const index = configurationIndex >= 0 ? configurationIndex : (isConfigured ? 0 : -1);
        if (index < 0) return;

        const { methodName, methodArguments, promiseResolve } = queuedMethodCalls.splice(index, 1)[0];
        if (typeof window._ddgCurrent[methodName] === 'function') {
            try {
                const result = window._ddgCurrent[methodName](...methodArguments);
                if (methodName === 'configuration') {
                    isConfigured = true;
                }
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
        const current = window._ddgCurrent;
        const isLoaded = current && !current.isProxy;
        if (!isLoaded && methodName in target) {
            return target[methodName];
        }
        if (isLoaded && typeof current[methodName] !== 'function') {
            return current[methodName];
        }
        return (...args) => {
            const current = window._ddgCurrent;
            if (current && !current.isProxy &&
                (methodName === 'configuration' || isConfigured)) {
                const result = current[methodName](...args);
                if (methodName === 'configuration') {
                    isConfigured = true;
                    processQueuedMethodCalls();
                }
                return result;
            }
            return new Promise(resolve => {
                queuedMethodCalls.push({
                    methodName,
                    methodArguments: args,
                    promiseResolve: resolve
                });
                processQueuedMethodCalls();
            });
        };
    },
    has(target, property) {
        const current = window._ddgCurrent;
        return property in (current && !current.isProxy ? current : target);
    },
    set(target, property, value) {
        window._ddgCurrent[property] = value;
        return true;
    },
    ownKeys() {
        return Reflect.ownKeys(window._ddgCurrent);
    },
    getOwnPropertyDescriptor(target, property) {
        const descriptor = Object.getOwnPropertyDescriptor(window._ddgCurrent, property);
        return descriptor ? { ...descriptor, configurable: true } : undefined;
    },
    deleteProperty(target, property) {
        return Reflect.deleteProperty(window._ddgCurrent, property);
    }
});

/**
 * Override window.ddg setter to detect object replacement
 */
const dotdigitalProxy = createDotdigitalProxy();
Object.defineProperty(window, 'ddg', {
    get: () => dotdigitalProxy,
    set: (value)  => (window._ddgCurrent = value, triggerReadyEvent())
});

// Keep the facade in place when the SDK replaces the underlying object.
window._ddgCurrent = { init: initializeDotdigital, isProxy: true };
