<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from '@/design-system/Button.vue';
import Input from '@/design-system/Input.vue';
import FormField from '@/design-system/FormField.vue';

const form = useForm({ login: '', email: '', password: '' });
const currentYear = new Date().getFullYear();

const workshops = [
    {
        name: 'Karate',
        badge: 'DISCIPLINA',
        image: '/images/workshops/karate.jpg',
        desc: 'Fuerza, respeto y autocontrol para la vida',
        tagBg: 'bg-rose-600 text-white',
        orbBorder: 'ring-rose-500 shadow-rose-500/30',
        desktopStyle: 'top: calc(50% - 195px); left: calc(50% - 130px);',
        popupSide: 'right',
        pulseDelay: '0s',
    },
    {
        name: 'Teatro',
        badge: 'EXPRESIÓN',
        image: '/images/workshops/theatre.jpg',
        desc: 'Confianza, empatía, oratoria y expresión corporal',
        tagBg: 'bg-purple-600 text-white',
        orbBorder: 'ring-purple-500 shadow-purple-500/30',
        desktopStyle: 'top: calc(50% - 195px); left: calc(50% + 130px);',
        popupSide: 'left',
        pulseDelay: '0.4s',
    },
    {
        name: 'Arte y Pintura',
        badge: 'CREATIVIDAD',
        image: '/images/workshops/art.jpg',
        desc: 'Creatividad, dibujo, pintura y sensibilidad estética',
        tagBg: 'bg-pink-600 text-white',
        orbBorder: 'ring-pink-500 shadow-pink-500/30',
        desktopStyle: 'top: 50%; left: calc(50% - 245px);',
        popupSide: 'right',
        pulseDelay: '0.8s',
    },
    {
        name: 'Matemáticas',
        badge: 'LÓGICA',
        image: '/images/workshops/math.jpg',
        desc: 'Pensamiento analítico, resolución de retos y cálculo',
        tagBg: 'bg-blue-600 text-white',
        orbBorder: 'ring-blue-500 shadow-blue-500/30',
        desktopStyle: 'top: 50%; left: calc(50% + 245px);',
        popupSide: 'left',
        pulseDelay: '1.2s',
    },
    {
        name: 'Inglés',
        badge: 'IDIOMAS',
        image: '/images/workshops/english.jpg',
        desc: 'Apertura al mundo, vocabulario y comunicación',
        tagBg: 'bg-emerald-600 text-white',
        orbBorder: 'ring-emerald-500 shadow-emerald-500/30',
        desktopStyle: 'top: calc(50% + 195px); left: calc(50% - 130px);',
        popupSide: 'right',
        pulseDelay: '1.6s',
    },
    {
        name: 'Computación',
        badge: 'DIGITAL',
        image: '/images/workshops/computing.jpg',
        desc: 'Habilidades digitales, tecnología e innovación',
        tagBg: 'bg-amber-600 text-white',
        orbBorder: 'ring-amber-500 shadow-amber-500/30',
        desktopStyle: 'top: calc(50% + 195px); left: calc(50% + 130px);',
        popupSide: 'left',
        pulseDelay: '2s',
    },
];

const metrics = [
    {
        value: '6+',
        label: 'Talleres Activos',
        desc: 'Talleres de formación integral continua',
        tagBg: 'bg-rose-600 text-white',
        orbBorder: 'ring-rose-400 shadow-rose-400/25 text-rose-700 bg-rose-50',
        desktopStyle: 'top: calc(50% - 85px); left: 50%;',
        popupSide: 'right',
    },
    {
        value: '100%',
        label: 'Gratuito y Solidario',
        desc: 'Acceso universal y libre de costos',
        tagBg: 'bg-amber-600 text-white',
        orbBorder: 'ring-amber-400 shadow-amber-400/25 text-amber-700 bg-amber-50',
        desktopStyle: 'top: calc(50% + 75px); left: calc(50% - 75px);',
        popupSide: 'right',
    },
    {
        value: 'Futuro',
        label: 'Oportunidades',
        desc: 'Construyendo proyectos de vida',
        tagBg: 'bg-sky-600 text-white',
        orbBorder: 'ring-sky-400 shadow-sky-400/25 text-sky-700 bg-sky-50',
        desktopStyle: 'top: calc(50% + 75px); left: calc(50% + 75px);',
        popupSide: 'left',
    },
];

const activeMobileWorkshop = ref<string | null>(null);
const toggleMobileWorkshop = (name: string) => {
    activeMobileWorkshop.value = activeMobileWorkshop.value === name ? null : name;
};

const showPassword = ref(false);
</script>

<template>
    <Head title="Aula Virtual - Asociación Un Día de Esperanza" />

    <div class="min-h-screen bg-[#faf7f2] text-slate-800 flex flex-col justify-between selection:bg-rose-500 selection:text-white">
        <!-- Top Institutional Header -->
        <header class="w-full bg-white/90 backdrop-blur-md border-b border-stone-200 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <a
                    href="https://www.undiadeesperanzaperu.org/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-3.5 group transition-transform duration-200 hover:scale-[1.01]"
                >
                    <div class="relative w-12 h-12 rounded-xl bg-white shadow-sm ring-1 ring-stone-200 flex items-center justify-center overflow-hidden p-1">
                        <img
                            src="/images/workshops/logo.jpeg"
                            alt="Logo Asociación Un Día de Esperanza"
                            class="w-full h-full object-contain"
                            onerror="this.style.display='none'"
                        />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-black text-sm tracking-wide text-rose-700 uppercase group-hover:text-rose-800 transition-colors">
                                Asociación Un Día de Esperanza
                            </span>
                            <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                ONG PERÚ 🇵🇪
                            </span>
                        </div>
                        <p class="text-xs text-stone-500 font-medium">Campus y Aula Virtual de Aprendizaje</p>
                    </div>
                </a>

                <div class="flex items-center gap-3">
                    <a
                        href="https://www.instagram.com/undiadeesperanza"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden md:flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-stone-600 hover:text-rose-600 hover:bg-rose-50/80 transition-all border border-stone-200"
                        title="Síguenos en Instagram"
                    >
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                        <span>@undiadeesperanza</span>
                    </a>

                    <a
                        href="https://www.undiadeesperanzaperu.org/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 shadow-sm transition-all"
                    >
                        <span>Portal Web ONG</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Landing Content (2 Columns Layout) -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-start">
                
                <!-- Left Column: Hero NGO Narrative & Formative Workshops -->
                <div class="lg:col-span-7 flex flex-col space-y-8">
                    
                    <!-- Purpose Badge -->
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-rose-100/80 border border-rose-200 text-rose-800 text-xs font-semibold w-fit">
                        <span class="flex h-2 w-2 rounded-full bg-rose-600 animate-pulse"></span>
                        <span>Educando con amor y construyendo futuro en Perú</span>
                    </div>

                    <!-- Hero Headline -->
                    <div>
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                            Tu educación abre puertas, <br class="hidden sm:inline" />
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-rose-600 via-red-600 to-amber-600">
                                ellos abren su futuro
                            </span>
                        </h1>
                        <p class="mt-4 text-base sm:text-lg text-stone-600 leading-relaxed font-normal">
                            Bienvenidos al espacio digital de la <strong>Asociación Un Día de Esperanza</strong>.
                            Brindamos formación integral gratuita a niños, niñas y jóvenes mediante talleres artísticos, deportivos, académicos y tecnológicos.
                        </p>
                    </div>

                    <!-- Ecosistema Atómico Formativo (Sin Card, Órbitas Atómicas e Iconos Expansibles en Hover) -->
                    <div class="relative w-full py-2">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-600 animate-pulse"></span>
                                <h2 class="text-xs font-bold tracking-widest uppercase text-stone-500">
                                    ÁTOMO FORMATIVO • TALLERES Y VALORES
                                </h2>
                            </div>
                            <span class="text-xs font-medium text-stone-400 hidden sm:inline">Pasa el cursor sobre cada electrón</span>
                        </div>

                        <!-- Atomic Canvas (Completamente sin tarjeta / Frameless) -->
                        <div class="relative w-full overflow-visible">
                            <!-- Desktop Atomic System (lg screens) -->
                            <div class="hidden lg:block relative w-full h-[500px]" aria-label="Estructura atómica de talleres formativos y valores">
                                <!-- 3 Intersecting Atomic Orbital Ellipses ⚛️ -->
                                <div class="atom-orbit atom-orbit--1" aria-hidden="true"></div>
                                <div class="atom-orbit atom-orbit--2" aria-hidden="true"></div>
                                <div class="atom-orbit atom-orbit--3" aria-hidden="true"></div>
                                <div class="atom-orbit atom-orbit--core" aria-hidden="true"></div>

                                <!-- Central Nucleus: Núcleo Atómico con Logotipo Institucional -->
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-10 flex flex-col items-center">
                                    <div class="relative w-20 h-20 rounded-full p-1 bg-gradient-to-tr from-rose-600 via-amber-400 to-rose-500 shadow-xl shadow-rose-500/25 animate-pulse-gentle">
                                        <div class="w-full h-full rounded-full bg-white flex items-center justify-center overflow-hidden p-1.5 border-2 border-white shadow-inner">
                                            <img
                                                src="/images/workshops/logo.jpeg"
                                                alt="Logo Asociación Un Día de Esperanza"
                                                class="w-full h-full object-contain"
                                                onerror="this.style.display='none'"
                                            />
                                        </div>
                                    </div>
                                    <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/90 backdrop-blur-sm border border-stone-200/90 shadow-sm">
                                        <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                        <span class="text-[10px] font-black tracking-wide text-rose-800 uppercase">
                                            Un Día de Esperanza
                                        </span>
                                    </div>
                                </div>

                                <!-- 3 Metric Quantum Particles (Inner Orbitals) -->
                                <div
                                    v-for="metric in metrics"
                                    :key="metric.label"
                                    class="absolute -translate-x-1/2 -translate-y-1/2 z-20 hover:z-50 focus-within:z-50 group"
                                    :style="metric.desktopStyle"
                                >
                                    <!-- Metric Orb Node (solo valor visible, animación directa al nodo) -->
                                    <div
                                        :class="[
                                            'relative w-10 h-10 rounded-full border flex items-center justify-center cursor-pointer shadow-md transition-all duration-300 atom-icon-pulse group-hover:scale-125 group-hover:shadow-lg',
                                            metric.orbBorder
                                        ]"
                                    >
                                        <span class="text-xs font-black tracking-tight leading-none">{{ metric.value }}</span>
                                    </div>

                                    <!-- Expanding Card on Hover (z-50 siempre por encima) -->
                                    <div
                                        :class="[
                                            'absolute top-1/2 -translate-y-1/2 pointer-events-none opacity-0 group-hover:opacity-100 group-hover:pointer-events-auto transition-all duration-300 ease-out z-50',
                                            metric.popupSide === 'right' ? 'left-full ml-3 -translate-x-2 group-hover:translate-x-0' : 'right-full mr-3 translate-x-2 group-hover:translate-x-0'
                                        ]"
                                    >
                                        <div class="px-3 py-2 rounded-xl bg-slate-900/95 text-white shadow-2xl backdrop-blur-md border border-slate-700/80 min-w-[150px] whitespace-nowrap">
                                            <div class="font-extrabold text-xs text-rose-300">{{ metric.value }} {{ metric.label }}</div>
                                            <p class="text-[10px] text-stone-300 font-normal">{{ metric.desc }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- 6 Workshop Electrons (Solo Iconos Visibles • Animación directa al icono • Expanden en Hover) -->
                                <div
                                    v-for="workshop in workshops"
                                    :key="workshop.name"
                                    class="absolute -translate-x-1/2 -translate-y-1/2 z-20 hover:z-50 focus-within:z-50 group"
                                    :style="workshop.desktopStyle"
                                >
                                    <!-- Pure Circular Icon Orb con Animación Pulsante Directa -->
                                    <div
                                        :class="[
                                            'relative w-12 h-12 rounded-full p-0.5 bg-white ring-2 ring-offset-2 flex items-center justify-center flex-shrink-0 cursor-pointer shadow-md transition-all duration-300 atom-icon-pulse group-hover:scale-125 group-hover:shadow-2xl group-hover:ring-4',
                                            workshop.orbBorder
                                        ]"
                                        :style="{ animationDelay: workshop.pulseDelay }"
                                    >
                                        <img
                                            :src="workshop.image"
                                            :alt="workshop.name"
                                            class="w-full h-full object-contain rounded-full"
                                            loading="lazy"
                                        />
                                    </div>

                                    <!-- Expands in Hover: Shows Name, Badge and Description (z-50 Siempre Por Encima de Todos los Iconos) -->
                                    <div
                                        :class="[
                                            'absolute top-1/2 -translate-y-1/2 pointer-events-none opacity-0 group-hover:opacity-100 group-hover:pointer-events-auto transition-all duration-300 ease-out z-50',
                                            workshop.popupSide === 'right' ? 'left-full ml-3 -translate-x-2 group-hover:translate-x-0' : 'right-full mr-3 translate-x-2 group-hover:translate-x-0'
                                        ]"
                                    >
                                        <div class="p-3 rounded-2xl bg-slate-900/95 text-white shadow-2xl backdrop-blur-md border border-slate-700/80 w-60">
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <span class="font-extrabold text-sm text-white tracking-tight">{{ workshop.name }}</span>
                                                <span :class="['text-[8px] font-black uppercase px-2 py-0.5 rounded-full', workshop.tagBg]">
                                                    {{ workshop.badge }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-stone-300 leading-snug">
                                                {{ workshop.desc }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile / Tablet Fallback (< lg screens, Sin Tarjeta) -->
                            <div class="lg:hidden flex flex-col items-center space-y-5 py-4">
                                <!-- Mobile Nucleus -->
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full p-1 bg-gradient-to-tr from-rose-600 via-amber-400 to-rose-500 shadow-lg">
                                        <div class="w-full h-full rounded-full bg-white flex items-center justify-center overflow-hidden p-1">
                                            <img
                                                src="/images/workshops/logo.jpeg"
                                                alt="Logo Asociación Un Día de Esperanza"
                                                class="w-full h-full object-contain"
                                                onerror="this.style.display='none'"
                                            />
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-rose-800 mt-1.5">Un Día de Esperanza</span>
                                </div>

                                <!-- Mobile Metric Particles Strip -->
                                <div class="flex items-center justify-center gap-3 w-full">
                                    <div
                                        v-for="metric in metrics"
                                        :key="metric.label"
                                        class="px-3 py-1 rounded-full border text-center flex items-center gap-1.5 shadow-sm"
                                        :class="metric.orbBorder"
                                    >
                                        <span class="text-xs font-black">{{ metric.value }}</span>
                                        <span class="text-[9px] font-bold uppercase opacity-85">{{ metric.label }}</span>
                                    </div>
                                </div>

                                <!-- Mobile Workshop Icons Row (Solo Iconos, Tocar para expandir) -->
                                <div class="w-full">
                                    <div class="flex items-center justify-center gap-3 sm:gap-4 flex-wrap">
                                        <button
                                            v-for="workshop in workshops"
                                            :key="workshop.name"
                                            type="button"
                                            @click="toggleMobileWorkshop(workshop.name)"
                                            :class="[
                                                'relative w-12 h-12 rounded-full p-0.5 bg-white ring-2 ring-offset-2 flex-shrink-0 transition-transform duration-200 active:scale-95 shadow-md',
                                                workshop.orbBorder,
                                                activeMobileWorkshop === workshop.name ? 'scale-110 ring-4' : ''
                                            ]"
                                            :title="workshop.name"
                                        >
                                            <img
                                                :src="workshop.image"
                                                :alt="workshop.name"
                                                class="w-full h-full object-contain rounded-full"
                                                loading="lazy"
                                            />
                                        </button>
                                    </div>

                                    <!-- Expanded Details on Tap (Mobile) -->
                                    <div
                                        v-if="activeMobileWorkshop"
                                        class="mt-4 p-3.5 rounded-2xl bg-slate-900 text-white shadow-xl border border-slate-700/80 transition-all duration-200"
                                    >
                                        <template v-for="ws in workshops" :key="ws.name">
                                            <div v-if="activeMobileWorkshop === ws.name">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="font-extrabold text-sm text-white">{{ ws.name }}</span>
                                                    <span :class="['text-[8px] font-black uppercase px-2 py-0.5 rounded-full', ws.tagBg]">
                                                        {{ ws.badge }}
                                                    </span>
                                                </div>
                                                <p class="text-xs text-stone-300 leading-snug">{{ ws.desc }}</p>
                                            </div>
                                        </template>
                                    </div>
                                    <p v-else class="text-center text-[11px] text-stone-400 mt-2 font-medium">
                                        Toca un icono para ver el taller
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Solidarity / Mission Quote Banner -->
                    <div class="p-4 rounded-xl bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-transparent border-l-4 border-rose-600 flex items-center justify-between gap-4">
                        <div class="text-xs sm:text-sm text-stone-700 italic">
                            "Un Día de Esperanza brinda alimentación, educación y afecto a niños en situación de vulnerabilidad."
                        </div>
                        <a
                            href="https://www.undiadeesperanzaperu.org/"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="whitespace-nowrap text-xs font-bold text-rose-700 hover:text-rose-800 underline underline-offset-2 flex-shrink-0"
                        >
                            Sumar como voluntario →
                        </a>
                    </div>

                </div>

                <!-- Right Column: Student / Teacher Login Form Card -->
                <div class="lg:col-span-5">
                    <div class="bg-white rounded-2xl border border-stone-200/90 shadow-xl shadow-stone-200/50 p-6 sm:p-8 relative overflow-hidden">
                        
                        <!-- Top Decorative Accent -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-rose-600 via-amber-500 to-red-600"></div>

                        <!-- Card Header -->
                        <div class="mb-6">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-stone-100 text-stone-700 text-xs font-semibold mb-3">
                                <svg class="w-3.5 h-3.5 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>Ingreso al Aula</span>
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight">¡Bienvenido(a)!</h2>
                            <p class="mt-1 text-sm text-stone-500">
                                Ingresa con tu usuario institucional o correo para acceder a tus clases y materiales
                            </p>
                        </div>

                        <!-- Login Form -->
                        <form class="flex flex-col gap-4" @submit.prevent="form.post('/login')">
                            <FormField
                                id="login"
                                label="Usuario o Correo Institucional"
                                :error="form.errors.login || form.errors.email"
                            >
                                <Input
                                    id="login"
                                    v-model="form.login"
                                    type="text"
                                    autocomplete="username"
                                    placeholder="ej. jperez o usuario@correo.com"
                                    :error="form.errors.login || form.errors.email"
                                    class="w-full text-sm"
                                />
                            </FormField>

                            <FormField
                                id="password"
                                label="Contraseña"
                                :error="form.errors.password"
                            >
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="current-password"
                                    placeholder="Ingresa tu contraseña"
                                    :error="form.errors.password"
                                    class="w-full text-sm"
                                />
                            </FormField>

                            <div class="pt-2">
                                <Button
                                    type="submit"
                                    :loading="form.processing"
                                    class="w-full justify-center !bg-gradient-to-r !from-rose-600 !to-red-600 hover:!from-rose-700 hover:!to-red-700 !text-white font-bold py-2.5 shadow-md shadow-rose-600/20"
                                >
                                    Ingresar al Aula Virtual
                                </Button>
                            </div>
                        </form>

                        <!-- Card Institutional Assistance Footer (Complies with REQ-028: admin password reset) -->
                        <div class="mt-6 pt-5 border-t border-stone-100 flex flex-col gap-3">
                            <div class="flex items-start gap-2.5 text-xs text-stone-500 bg-stone-50 p-3 rounded-lg border border-stone-200/60">
                                <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>
                                    ¿Olvidaste tu contraseña? Solicita su restablecimiento a tu <strong>docente o administrador</strong> de la Asociación.
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-stone-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    Acceso institucional seguro
                                </span>
                                <a
                                    href="https://www.undiadeesperanzaperu.org/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="hover:text-rose-600 transition-colors"
                                >
                                    Soporte ONG
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </main>

        <!-- Institutional Footer -->
        <footer class="w-full bg-white border-t border-stone-200 py-6 mt-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-stone-500">
                <div class="flex items-center gap-3">
                    <img
                        src="/images/workshops/logo.jpeg"
                        alt="Logo Un Día de Esperanza"
                        class="h-6 w-auto object-contain"
                        onerror="this.style.display='none'"
                    />
                    <span>
                        © {{ currentYear }} <strong>Asociación Un Día de Esperanza</strong>. Transformando vidas a través de la educación.
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    <a
                        href="https://www.undiadeesperanzaperu.org/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hover:text-rose-600 transition-colors font-medium"
                    >
                        undiadeesperanzaperu.org
                    </a>
                    <span>•</span>
                    <a
                        href="https://www.instagram.com/undiadeesperanza"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hover:text-rose-600 transition-colors font-medium"
                    >
                        Instagram
                    </a>
                    <span>•</span>
                    <span class="text-stone-400">Lima, Perú</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.atom-orbit {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 490px;
    height: 180px;
    border-radius: 50%;
    pointer-events: none;
    transform-origin: center center;
}

.atom-orbit--1 {
    transform: translate(-50%, -50%) rotate(0deg);
    border: 1.5px dashed rgba(225, 29, 72, 0.45);
    box-shadow: 0 0 30px rgba(225, 29, 72, 0.05);
}

.atom-orbit--2 {
    transform: translate(-50%, -50%) rotate(60deg);
    border: 1.5px dashed rgba(124, 58, 237, 0.45);
    box-shadow: 0 0 30px rgba(124, 58, 237, 0.05);
}

.atom-orbit--3 {
    transform: translate(-50%, -50%) rotate(-60deg);
    border: 1.5px dashed rgba(13, 148, 136, 0.45);
    box-shadow: 0 0 30px rgba(13, 148, 136, 0.05);
}

.atom-orbit--core {
    width: 170px;
    height: 170px;
    border: 1.5px dotted rgba(245, 158, 11, 0.5);
    border-radius: 50%;
    transform: translate(-50%, -50%);
}

@keyframes pulse-gentle {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.25);
    }
    50% {
        transform: scale(1.04);
        box-shadow: 0 16px 32px -5px rgba(245, 158, 11, 0.35);
    }
}

.animate-pulse-gentle {
    animation: pulse-gentle 4s ease-in-out infinite;
}

@keyframes atom-icon-pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.08);
    }
}

.atom-icon-pulse {
    animation: atom-icon-pulse 3s ease-in-out infinite;
    will-change: transform;
}

.group:hover .atom-icon-pulse {
    animation-play-state: paused;
}

@media (prefers-reduced-motion: reduce) {
    .animate-pulse-gentle,
    .atom-icon-pulse {
        animation: none;
    }
}
</style>
