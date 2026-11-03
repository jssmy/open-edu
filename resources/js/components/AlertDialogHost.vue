<script setup lang="ts">
import AlertDialog from '@/design-system/AlertDialog.vue';
import { alertDialogState, respondToAlertDialog } from '@/composables/useAlertDialog';
</script>

<template>
    <AlertDialog
        :open="alertDialogState.open"
        :variant="alertDialogState.variant"
        :title="alertDialogState.title"
        :message="alertDialogState.message"
        :confirm-label="alertDialogState.confirmLabel"
        :cancel-label="alertDialogState.cancelLabel"
        @confirm="respondToAlertDialog(true)"
        @cancel="respondToAlertDialog(false)"
    />
</template>
