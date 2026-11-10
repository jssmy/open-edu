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
        tagBg: 'bg-red-50 text-red-700 border-red-200',
        cardBorder: 'hover:border-red-300',
    },
    {
        name: 'Teatro',
        badge: 'EXPRESIÓN',
        image: '/images/workshops/theatre.jpg',
        desc: 'Confianza, empatía, oratoria y expresión corporal',
        tagBg: 'bg-purple-50 text-purple-700 border-purple-200',
        cardBorder: 'hover:border-purple-300',
    },
    {
        name: 'Arte y Pintura',
        badge: 'CREATIVIDAD',
        image: '/images/workshops/art.jpg',
        desc: 'Creatividad, dibujo, pintura y sensibilidad estética',
        tagBg: 'bg-pink-50 text-pink-700 border-pink-200',
        cardBorder: 'hover:border-pink-300',
    },
    {
        name: 'Matemáticas',
        badge: 'LÓGICA',
        image: '/images/workshops/math.jpg',
        desc: 'Pensamiento analítico, resolución de retos y cálculo',
        tagBg: 'bg-blue-50 text-blue-700 border-blue-200',
        cardBorder: 'hover:border-blue-300',
    },
    {
        name: 'Inglés',
        badge: 'IDIOMAS',
        image: '/images/workshops/english.jpg',
        desc: 'Apertura al mundo, vocabulario y comunicación',
        tagBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        cardBorder: 'hover:border-emerald-300',
    },
    {
        name: 'Computación',
        badge: 'DIGITAL',
        image: '/images/workshops/computing.jpg',
        desc: 'Habilidades digitales, tecnología e innovación',
        tagBg: 'bg-amber-50 text-amber-700 border-amber-200',
        cardBorder: 'hover:border-amber-300',
    },
];

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
                            src="https://www.undiadeesperanzaperu.org/wp-content/uploads/2023/10/logo-un-dia-de-esperanza.jpg"
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

                    <!-- Impact Metrics Strip -->
                    <div class="grid grid-cols-3 gap-3 sm:gap-4 p-4 rounded-2xl bg-white border border-stone-200 shadow-sm">
                        <div class="text-center p-2 rounded-xl bg-rose-50/60 border border-rose-100">
                            <span class="block text-2xl sm:text-3xl font-black text-rose-700">6+</span>
                            <span class="text-[11px] sm:text-xs font-semibold text-rose-900 tracking-wider uppercase">Talleres Activos</span>
                        </div>
                        <div class="text-center p-2 rounded-xl bg-amber-50/60 border border-amber-100">
                            <span class="block text-2xl sm:text-3xl font-black text-amber-700">100%</span>
                            <span class="text-[11px] sm:text-xs font-semibold text-amber-900 tracking-wider uppercase">Gratuito y Solidario</span>
                        </div>
                        <div class="text-center p-2 rounded-xl bg-sky-50/60 border border-sky-100">
                            <span class="block text-2xl sm:text-3xl font-black text-sky-700">Futuro</span>
                            <span class="text-[11px] sm:text-xs font-semibold text-sky-900 tracking-wider uppercase">Oportunidades</span>
                        </div>
                    </div>

                    <!-- Workshops Grid -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-600"></span>
                                <h2 class="text-xs font-bold tracking-widest uppercase text-stone-500">
                                    NUESTROS TALLERES FORMATIVOS
                                </h2>
                            </div>
                            <span class="text-xs font-medium text-stone-400">Aprende, Descubre y Crece</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div
                                v-for="workshop in workshops"
                                :key="workshop.name"
                                :class="['p-3.5 rounded-xl bg-white border border-stone-200 shadow-sm transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 flex items-center gap-3.5', workshop.cardBorder]"
                            >
                                <div class="w-14 h-14 rounded-xl bg-stone-50 border border-stone-100 flex-shrink-0 overflow-hidden p-1 shadow-inner flex items-center justify-center">
                                    <img
                                        :src="workshop.image"
                                        :alt="workshop.name"
                                        class="w-full h-full object-contain rounded-lg hover:scale-105 transition-transform duration-200"
                                        loading="lazy"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <h3 class="font-bold text-sm text-slate-900 truncate">{{ workshop.name }}</h3>
                                        <span :class="['text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded border', workshop.tagBg]">
                                            {{ workshop.badge }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-stone-500 leading-snug line-clamp-2">
                                        {{ workshop.desc }}
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
                        src="https://www.undiadeesperanzaperu.org/wp-content/uploads/2023/10/logo-un-dia-de-esperanza.jpg"
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
