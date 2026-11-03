export const tokens = {
    colors: {
        primary: { DEFAULT: '#2563eb', hover: '#1d4ed8', soft: '#dbeafe' },
        danger: { DEFAULT: '#b91c1c', hover: '#991b1b', soft: '#fee2e2' },
        success: { DEFAULT: '#166534', soft: '#dcfce7' },
        warning: { DEFAULT: '#92400e', soft: '#fef3c7' },
        surface: '#f8fafc',
        panel: '#ffffff',
        border: '#e2e8f0',
        text: { DEFAULT: '#0f172a', muted: '#64748b' },
    },
    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
    radius: { control: '0.5rem', card: '0.75rem' },
    shadow: { card: '0 1px 3px rgba(15, 23, 42, 0.1)', overlay: '0 10px 30px rgba(15, 23, 42, 0.2)' },
    spacing: { gutter: '1.5rem' },
    breakpoints: { sm: 640, md: 768, lg: 1024, xl: 1280 },
} as const;
