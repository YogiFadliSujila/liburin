import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Composable for listening to real-time trip events
 */
export function useTripChannel(tripId, options = {}) {
    const {
        onPaymentUpdated = null,
        onWithdrawalVote = null,
        onMemberJoined = null,
    } = options;

    const isConnected = ref(false);

    onMounted(() => {
        if (!window.Echo || !tripId) return;

        const channel = window.Echo.private(`trip.${tripId}`);

        channel.subscribed(() => {
            isConnected.value = true;
            console.log(`Connected to trip.${tripId} channel`);
        });

        // Listen for payment updates
        if (onPaymentUpdated) {
            channel.listen('.payment.updated', (data) => {
                console.log('Payment updated:', data);
                onPaymentUpdated(data);
            });
        }

        // Listen for withdrawal vote updates
        if (onWithdrawalVote) {
            channel.listen('.withdrawal.vote', (data) => {
                console.log('Withdrawal vote:', data);
                onWithdrawalVote(data);
            });
        }

        // Listen for new members
        if (onMemberJoined) {
            channel.listen('.member.joined', (data) => {
                console.log('Member joined:', data);
                onMemberJoined(data);
            });
        }
    });

    onUnmounted(() => {
        if (window.Echo && tripId) {
            window.Echo.leave(`trip.${tripId}`);
            isConnected.value = false;
        }
    });

    return {
        isConnected,
    };
}

/**
 * Composable for toast notifications
 */
export function useToast() {
    const toasts = ref([]);

    const show = (message, type = 'info', duration = 5000) => {
        const id = Date.now();
        toasts.value.push({ id, message, type });

        if (duration > 0) {
            setTimeout(() => {
                remove(id);
            }, duration);
        }
    };

    const remove = (id) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (message, duration) => show(message, 'success', duration);
    const error = (message, duration) => show(message, 'error', duration);
    const info = (message, duration) => show(message, 'info', duration);
    const warning = (message, duration) => show(message, 'warning', duration);

    return {
        toasts,
        show,
        remove,
        success,
        error,
        info,
        warning,
    };
}
