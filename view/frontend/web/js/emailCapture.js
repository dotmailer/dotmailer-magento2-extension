/**
 * Configuration for email capture.
 *
 * @type {{
 *  layout: string,
 *  layout_postable: string[],
 *  layout_identifiable: string[],
 *  layout_identifiers: {string: string[]},
 *  capture_url: string,
 *  form_key: string, capture_url: string
 * }}
 */
const config = JSON.parse(document.getElementById('dotdigital-email-capture-config').textContent);

/**
 * Determine the layout type based on the configuration.
 *
 * @type {{
 *  string: string[]
 * }}
 */
const layoutsIdentifiers = config.layout_identifiers;

/**
 * Determine the layout type based on the configuration.
 *
 * @type {string|string|PlaneLayout[]|*}
 */
const currentLayoutType = layoutsIdentifiers.hasOwnProperty(config.layout) ? config.layout : 'default';

/**
 * Layout-actionable functions for specific page types.
 *
 * @type {(function(*, *): *)|*}
 */
const postEmailCapture = async (url, email) => {
    await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `email=${encodeURIComponent(email)}&form_key=${encodeURIComponent(config.form_key)}`
    }).catch(error => console.error('Error:', error));
}

/**
 * Capture an email address from the given element.
 *
 * @param element
 * @param layoutType
 */
const capture = async (element, layoutType) => {
    if (!/^([+\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/.test(element.value)) {
        return;
    }

    if(config.layout_postable.includes(layoutType)) {
        await postEmailCapture(config.capture_url, element.value);
    }

    if(window.ddg && typeof window.ddg.identify === 'function') {
        window.ddg.identify({'email': element.value});
    }
}

/**
 * Initialize email capture by attaching event listeners to relevant fields.
 *
 * @param event
 */
const emailCapture = async (event) => {
    for (const layoutType of [currentLayoutType, 'default']) {
        if (!layoutsIdentifiers[layoutType]) continue;
        for (const field of layoutsIdentifiers[layoutType]) {
            if (event.target.matches(field)) {
                await capture(event.target, layoutType);
                return;
            }
        }
    }
}

/**
 * Initialize email capture when the DOM is fully loaded.
 *
 * @event DOMContentLoaded
 */
document.addEventListener("blur", (event) => emailCapture(event), { once: false, capture: true });
