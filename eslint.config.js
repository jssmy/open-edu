import js from '@eslint/js';
import ts from 'typescript-eslint';
import vue from 'eslint-plugin-vue';
import vueParser from 'vue-eslint-parser';
import prettier from 'eslint-config-prettier';

// T115: sin colores ni estilos fijos fuera del sistema de diseño
const noHardcodedStyle = {
    selector: "Literal[value=/^#[0-9a-fA-F]{3,8}$|^(rgb|hsl)a?\\(/]",
    message: 'Usa los tokens del sistema de diseño en lugar de colores fijos.',
};

export default [
    {
        ignores: [
            'node_modules/',
            'vendor/',
            'public/',
            'storage/',
            'bootstrap/cache/',
            'build/',
            'dist/',
            'coverage/',
            '**/*.min.js',
        ],
    },
    js.configs.recommended,
    ...ts.configs.recommended,
    ...vue.configs['flat/recommended'],
    {
        files: ['**/*.vue'],
        languageOptions: { parser: vueParser, parserOptions: { parser: ts.parser } },
    },
    {
        files: ['resources/js/**/*.{ts,vue}'],
        ignores: ['resources/js/design-system/**', 'resources/js/__tests__/**'],
        rules: { 'no-restricted-syntax': ['error', noHardcodedStyle] },
    },
    { rules: { 'vue/multi-word-component-names': 'off' } },
    prettier,
];
