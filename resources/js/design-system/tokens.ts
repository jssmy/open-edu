export const tokens = {
    colors: {
        primary: { DEFAULT: '#1e3a5f', hover: '#17324f', soft: '#e8eef5' },
        secondary: { DEFAULT: '#0f766e', hover: '#115e59', soft: '#e6f4f1' },
        accent: { DEFAULT: '#8a5a12', soft: '#f7f0df' },
        danger: { DEFAULT: '#b42318', hover: '#912018', soft: '#fef0ee' },
        success: { DEFAULT: '#176b48', soft: '#e8f4ed' },
        warning: { DEFAULT: '#8a5a12', soft: '#f7f0df' },
        surface: '#f3f6f9',
        panel: '#ffffff',
        border: '#d8e0e8',
        text: { DEFAULT: '#1d2939', muted: '#526174' },
    },
    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
    radius: { control: '0.5rem', card: '0.75rem' },
    shadow: {
        card: '0 1px 3px rgba(29, 41, 57, 0.06), 0 1px 2px rgba(29, 41, 57, 0.04)',
        overlay: '0 12px 32px rgba(29, 41, 57, 0.16)',
    },
    spacing: { gutter: '1.5rem' },
    breakpoints: { sm: 640, md: 768, lg: 1024, xl: 1280 },
} as const;
