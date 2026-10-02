import { ref } from 'vue';

export function useModal() {
    const isOpen = ref(false);
    const data = ref(null);

    const open = (payload = null) => {
        data.value = payload;
        isOpen.value = true;
    };

    const close = () => {
        isOpen.value = false;
        setTimeout(() => {
            data.value = null;
        }, 200);
    };

    return {
        isOpen,
        data,
        open,
        close,
    };
}
