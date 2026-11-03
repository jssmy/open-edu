import { tokens } from './resources/js/design-system/tokens';

export default {
    theme: {
        extend: {
            colors: tokens.colors,
            fontFamily: { sans: tokens.fontFamily.sans },
            borderRadius: tokens.radius,
            boxShadow: tokens.shadow,
            spacing: tokens.spacing,
        },
    },
};
