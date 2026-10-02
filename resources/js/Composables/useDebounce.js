import { ref, watch } from 'vue';

export function useDebounce(fn, delay = 350) {
    let timeoutId = null;

    const debouncedFn = (...args) => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        timeoutId = setTimeout(() => {
            fn(...args);
            timeoutId = null;
        }, delay);
    };

    const cancel = () => {
        if (timeoutId) {
            clearTimeout(timeoutId);
            timeoutId = null;
        }
    };

    return {
        debouncedFn,
        cancel,
    };
}
