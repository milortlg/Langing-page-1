<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface Profile {
    id: number;
    name: string;
    biography: string;
    description: string | null;
    is_online: boolean;
    photos_count: number;
    is_within_online_hours: boolean;
    videos_count: number;
    likes_count: number;
    action_label: string;
    rencontre_primary_label: string | null;
    rencontre_secondary_label: string | null;
    script_url: string | null;
    banner_url: string | null;
    avatar_url: string | null;
    logo_url: string | null;
    certification_url: string | null;
}

interface Post {
    id: number;
    content: string | null;
    type: 'photo' | 'video' | 'live' | 'rencontre';
    duration?: string | null;
    likes_count: number;
    is_visible: boolean;
    is_blurred: boolean;
    is_live: boolean;
    created_at: string;
    media: Array<{
        id: number;
        url: string;
        type: string;
    }>;
}

const props = defineProps<{
    profile: Profile;
    posts: Post[];
}>();

/* ------------------------------------------------------------------
 * Textes de l'offre (carte "Mon VIP" sous la grille).
 * Le prix réel et le paiement sont gérés par le script Prelinker dans la
 * popup ; cette carte est un rappel visuel. Modifie les valeurs ici si
 * ton offre change.
 * ------------------------------------------------------------------ */
const OFFER = {
    // Nom du service vers lequel renvoie le bouton (affiché dans la popup)
    service: 'MonVip',
    price: '1.04',
    oldPrice: '28.93',
    per: 'par semaine',
    perDay: 'soit 0.15 € / jour',
    was: 'au lieu de 28.93 € pour 2 semaines',
    // À COMPLÉTER : prix appliqué après l'essai (ex. « puis 28,93 € toutes les 2 semaines »)
    renewal: 'puis 28,93 € toutes les 2 semaines, sans engagement',
    features: [
        'Accès complet aux profils premium',
        'Messages privés illimités',
        'Contenu exclusif en avant-première',
        'Accès au site de rencontre',
    ],
};

const key = ref(0);
const activeTab = ref<'posts' | 'live' | 'rencontre'>('posts');
const showStickyBar = ref(false);
const showPremium = ref(false);
const showRencontreModal = ref(false);
const stickyAnchorRef = ref<HTMLElement | null>(null);

const postsFeed = computed(() =>
    props.posts.filter((p) => p.type === 'photo' || p.type === 'video'),
);
const liveFeed = computed(() => props.posts.filter((p) => p.type === 'live'));

const visiblePosts = computed(() =>
    activeTab.value === 'live' ? liveFeed.value : postsFeed.value,
);

const hasLivePost = computed(() => props.posts.some((p) => p.type === 'live'));

const handle = computed(
    () => '@' + props.profile.name.toLowerCase().replace(/\s+/g, ''),
);

function cleanupSelector() {
    const el = document.querySelector('#selector');
    if (el) el.innerHTML = '';
}

watch(
    () => showRencontreModal.value,
    (isOpen) => {
        if (!isOpen) cleanupSelector();
    },
);

const isLive = () =>
    props.posts.some((post) => post.is_live) ||
    props.profile.is_within_online_hours;

const isOnline = () =>
    props.profile.is_online || props.profile.is_within_online_hours || isLive();

const triggerDebloquerCta = () => {
    const el = document.getElementById('ctaintro');
    if (!el) return;
    el.dispatchEvent(
        new MouseEvent('click', { bubbles: true, cancelable: true }),
    );
};

const openOffer = async () => {
    hasInteracted = true;
    showPremium.value = false;
    showRencontreModal.value = true;
    await nextTick();
    cleanupSelector();
    triggerDebloquerCta();
};

/* Popup des services : s'ouvre seule après 5 s si le visiteur n'a rien touché */
const AUTO_POPUP_DELAY = 5000;
let hasInteracted = false;
let autoPopupTimer: ReturnType<typeof setTimeout> | null = null;

const markInteracted = (e: Event) => {
    // Le clic qui ferme la popup ne compte pas comme une interaction "avant"
    if ((e.target as HTMLElement | null)?.closest?.('.mv-premium')) return;
    hasInteracted = true;
};

const goPremium = async () => {
    showPremium.value = false;
    await openOffer();
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') showPremium.value = false;
};

const handleScroll = () => {
    if (stickyAnchorRef.value) {
        const rect = stickyAnchorRef.value.getBoundingClientRect();
        showStickyBar.value = rect.bottom < 0;
    }
};

let ptScriptEl: HTMLScriptElement | null = null;

function loadExternalScript(src: string): Promise<void> {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(
            `script[data-ptprelinker="true"][src="${src}"]`,
        ) as HTMLScriptElement | null;
        if (existing) return resolve();

        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.defer = true;
        s.setAttribute('data-ptprelinker', 'true');
        s.onload = () => resolve();
        s.onerror = () =>
            reject(new Error(`Impossible de charger le script: ${src}`));
        document.head.appendChild(s);
        ptScriptEl = s;
    });
}

onMounted(async () => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('pointerdown', markInteracted, { passive: true });

    autoPopupTimer = setTimeout(() => {
        if (!hasInteracted && !showRencontreModal.value) {
            showPremium.value = true;
        }
    }, AUTO_POPUP_DELAY);

    if (props.profile.script_url) {
        try {
            await loadExternalScript(props.profile.script_url);
        } catch (e) {
            console.error(e);
        }
    }

    const params = new URLSearchParams(window.location.search);
    if (params.get('modal') === 'rencontre') {
        activeTab.value = 'rencontre';
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('pointerdown', markInteracted);
    if (autoPopupTimer) clearTimeout(autoPopupTimer);
    if (ptScriptEl?.parentNode) {
        ptScriptEl.parentNode.removeChild(ptScriptEl);
        ptScriptEl = null;
    }
});
</script>

<template>
    <Head :title="profile.name">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            rel="stylesheet"
            href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800"
        />
    </Head>

    <div class="mv min-h-screen">
        <!-- Bannière -->
        <div
            class="mv-banner"
            :style="
                profile.banner_url
                    ? { backgroundImage: `url(${profile.banner_url})` }
                    : {}
            "
        >
            <div class="mv-banner-fade"></div>
            <div v-if="profile.logo_url" class="mv-logo">
                <img :src="profile.logo_url" alt="Logo" loading="lazy" />
            </div>
        </div>
        <div class="mv-halo" aria-hidden="true"></div>

        <div class="mv-wrap">
            <!-- En-tête profil -->
            <div class="mv-head">
                <div class="mv-avatar">
                    <div class="mv-avatar-in">
                        <img
                            v-if="profile.avatar_url"
                            :src="profile.avatar_url"
                            :alt="profile.name"
                        />
                        <svg
                            v-else
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="mv-avatar-ph"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <span
                        v-if="isOnline()"
                        class="mv-online"
                        title="En ligne"
                    ></span>
                    <span v-if="isLive()" class="mv-livebadge">LIVE</span>
                </div>

                <div class="mv-head-actions">
                    <button
                        type="button"
                        class="mv-btn-ghost"
                        aria-label="Ajouter aux favoris"
                        title="Ajouter aux favoris"
                        @click="openOffer"
                    >
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M12 21s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2 4.4-9.5 9-9.5 9Z"
                            />
                        </svg>
                    </button>
                    <button type="button" class="mv-btn-msg" @click="openOffer">
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"
                            />
                        </svg>
                        M'écrire
                    </button>
                </div>
            </div>

            <h1 class="mv-name">
                {{ profile.name }}
                <img
                    v-if="profile.certification_url"
                    :src="profile.certification_url"
                    alt="Profil vérifié"
                    class="mv-cert"
                />
                <span v-else class="mv-verified" title="Profil vérifié">
                    <svg
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="#fff"
                        stroke-width="3.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m5 12 5 5L20 7" />
                    </svg>
                </span>
            </h1>
            <div class="mv-handle">{{ handle }}</div>

            <div class="mv-stats">
                <span>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <circle cx="9" cy="9" r="2" />
                        <path d="m21 15-5-5L5 21" />
                    </svg>
                    <b>{{ profile.photos_count }}</b> Photos
                </span>
                <span>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect x="2" y="6" width="14" height="12" rx="2" />
                        <path d="m22 8-6 4 6 4V8Z" />
                    </svg>
                    <b>{{ profile.videos_count }}</b> Vidéos
                </span>
                <span>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                    >
                        <path
                            d="M12 21s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2 4.4-9.5 9-9.5 9Z"
                        />
                    </svg>
                    <b>{{ profile.likes_count }}</b> Likes
                </span>
            </div>

            <p v-if="profile.biography" class="mv-bio">
                {{ profile.biography }}
            </p>

            <!-- Onglets -->
            <div ref="stickyAnchorRef" class="mv-tabs" role="tablist">
                <button
                    role="tab"
                    :aria-selected="activeTab === 'posts'"
                    @click="activeTab = 'posts'"
                >
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <path d="M3 9h18M3 15h18M9 3v18M15 3v18" />
                    </svg>
                    Posts
                </button>
                <button
                    role="tab"
                    :aria-selected="activeTab === 'live'"
                    @click="activeTab = 'live'"
                >
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect x="2" y="6" width="14" height="12" rx="2" />
                        <path d="m22 8-6 4 6 4V8Z" />
                    </svg>
                    Live
                    <i v-if="hasLivePost && isLive()" class="mv-reddot"></i>
                </button>
                <button
                    role="tab"
                    :aria-selected="activeTab === 'rencontre'"
                    @click="activeTab = 'rencontre'"
                >
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M12 21s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2 4.4-9.5 9-9.5 9Z"
                        />
                    </svg>
                    Rencontre
                </button>
            </div>

            <!-- Rencontre -->
            <div v-if="activeTab === 'rencontre'" class="mv-meet">
                <div class="mv-avatar mv-avatar-center">
                    <div class="mv-avatar-in">
                        <img
                            v-if="profile.avatar_url"
                            :src="profile.avatar_url"
                            :alt="profile.name"
                        />
                    </div>
                    <span v-if="isOnline()" class="mv-online"></span>
                    <span v-if="isLive()" class="mv-livebadge">LIVE</span>
                </div>
                <h3>{{ profile.name }} t'a envoyé une invitation</h3>
                <p>Elle est disponible pour discuter maintenant.</p>
                <button
                    type="button"
                    class="mv-cta mv-cta-pill"
                    @click="openOffer"
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                    >
                        <path
                            d="M12 21s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2 4.4-9.5 9-9.5 9Z"
                        />
                    </svg>
                    {{
                        profile.rencontre_primary_label ||
                        "Accepter l'invitation"
                    }}
                </button>
                <button type="button" class="mv-alt" @click="openOffer">
                    {{
                        profile.rencontre_secondary_label ||
                        'Découvrir le profil'
                    }}
                </button>
            </div>

            <!-- Grille Posts / Live -->
            <div v-else class="mv-grid">
                <button
                    v-for="post in visiblePosts"
                    :key="post.id"
                    type="button"
                    class="mv-tile"
                    :class="{ 'is-blur': post.is_blurred }"
                    @contextmenu.prevent
                    @click="openOffer"
                >
                    <template v-if="post.media && post.media.length > 0">
                        <img
                            v-if="post.media[0].type.startsWith('image')"
                            :src="post.media[0].url"
                            :alt="`Post ${post.id}`"
                            class="mv-tile-img"
                            draggable="false"
                        />
                        <div v-else class="mv-tile-video">
                            <svg
                                width="40"
                                height="40"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"
                                />
                            </svg>
                        </div>
                    </template>
                    <div v-else class="mv-tile-video"></div>

                    <span
                        v-if="
                            post.is_live &&
                            (post.type === 'live' || post.type === 'video')
                        "
                        class="mv-tile-live"
                    >
                        <i></i>LIVE
                    </span>
                    <span
                        v-if="
                            post.duration &&
                            (post.type === 'video' || post.type === 'live')
                        "
                        class="mv-tile-dur"
                    >
                        {{ post.duration }}
                    </span>
                    <span
                        v-if="post.media && post.media.length > 1"
                        class="mv-tile-more"
                    >
                        +{{ post.media.length - 1 }}
                    </span>

                    <span v-if="post.is_blurred" class="mv-tile-lock">
                        <i>
                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="4"
                                    y="11"
                                    width="16"
                                    height="10"
                                    rx="2"
                                />
                                <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                            </svg>
                        </i>
                        <em>
                            {{
                                post.is_live
                                    ? 'Accéder au live'
                                    : post.type === 'live'
                                      ? 'Replay'
                                      : 'Débloquer'
                            }}
                        </em>
                    </span>

                    <span class="mv-tile-likes">
                        <svg
                            width="12"
                            height="12"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                        >
                            <path
                                d="M12 21s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2 4.4-9.5 9-9.5 9Z"
                            />
                        </svg>
                        {{ post.likes_count }}
                    </span>
                </button>

                <div v-if="visiblePosts.length === 0" class="mv-empty">
                    Aucune publication pour le moment.
                </div>
            </div>

            <div class="mv-more">
                <button type="button" @click="openOffer">Voir plus</button>
            </div>

            <!-- Offre -->
            <section class="mv-offer" aria-label="Offre d'essai">
                <h3>Mon VIP · offre d'essai</h3>
                <div>
                    <div class="mv-price">
                        <small>€</small><span>{{ OFFER.price }}</span>
                    </div>
                </div>
                <div class="mv-per">
                    {{ OFFER.per }}, <em>{{ OFFER.perDay }}</em>
                </div>
                <div class="mv-was">{{ OFFER.was }}</div>
                <ul class="mv-feat">
                    <li v-for="f in OFFER.features" :key="f">
                        <i>
                            <svg
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="m5 12 5 5L20 7" />
                            </svg>
                        </i>
                        {{ f }}
                    </li>
                </ul>
                <!-- #ctaintro : repère utilisé par le script Prelinker -->
                <a
                    id="ctaintro"
                    class="mv-cta"
                    target="_blank"
                    rel="noopener noreferrer"
                    @click="showRencontreModal = true"
                >
                    {{ profile.action_label || "S'abonner au VIP" }}
                </a>
                <div class="mv-safe">
                    <svg
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect x="4" y="11" width="16" height="10" rx="2" />
                        <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                    </svg>
                    Paiement sécurisé · {{ OFFER.renewal }}
                </div>
            </section>

            <div class="mv-foot">
                Plateforme sécurisée • Paiement protégé • Support 24/7
            </div>
        </div>

        <!-- Popup abonnement (contenu injecté par Prelinker dans #selector) -->
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-show="showRencontreModal" :key="key" class="mv-modal">
                <div
                    class="mv-box"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Inscription"
                >
                    <button
                        type="button"
                        class="mv-x"
                        aria-label="Fermer"
                        @click="showRencontreModal = false"
                    >
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                        >
                            <path d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>

                    <div id="selector" class="mv-selector"></div>
                </div>
            </div>
        </Transition>

        <!-- Bouton flottant « Accès premium » -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-[130%] opacity-0"
            enter-to-class="translate-y-0 opacity-100"
        >
            <button
                v-if="!showPremium && !showRencontreModal"
                type="button"
                class="mv-premium-btn"
                @click="openOffer"
            >
                Accès premium · {{ OFFER.price.replace('.', ',') }} €
            </button>
        </Transition>

        <!-- Popup des services de l'offre -->
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showPremium"
                class="mv-modal mv-premium"
                @click.self="showPremium = false"
            >
                <div
                    class="mv-premium-box"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="mv-premium-title"
                >
                    <button
                        type="button"
                        class="mv-x"
                        aria-label="Fermer"
                        @click="showPremium = false"
                    >
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                        >
                            <path d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>
                    <span class="mv-premium-tag">Profils exclusifs</span>
                    <h2 id="mv-premium-title">Rejoins {{ OFFER.service }}</h2>
                    <span class="mv-premium-trial">Offre d'essai</span>
                    <div class="mv-premium-price">
                        <s>{{ OFFER.oldPrice.replace('.', ',') }} €</s>
                        <b>{{ OFFER.price.replace('.', ',') }} €</b>
                    </div>
                    <p class="mv-premium-renew">{{ OFFER.renewal }}</p>
                    <ul class="mv-premium-list">
                        <li v-for="f in OFFER.features" :key="f">{{ f }}</li>
                    </ul>
                    <button
                        type="button"
                        class="mv-premium-go"
                        @click="goPremium"
                    >
                        Profiter de l'offre
                    </button>
                    <p class="mv-premium-legal">
                        Service proposé par {{ OFFER.service }}. Prix et
                        conditions confirmés avant tout paiement.
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.mv {
    --mv-bg: #141216;
    --mv-card: #1f1b22;
    --mv-card2: #26212a;
    --mv-line: #3a323f;
    --mv-text: #f1ecf0;
    --mv-muted: #a69ea8;
    --mv-pink: #ec4899;
    --mv-rose: #f43f5e;
    --mv-grad: linear-gradient(90deg, #ec4899 0%, #f43f5e 50%, #fb923c 100%);
    --mv-glow: 0 10px 30px rgba(244, 63, 94, 0.28);
    background: var(--mv-bg);
    color: var(--mv-text);
    font-family:
        'Plus Jakarta Sans',
        system-ui,
        -apple-system,
        'Segoe UI',
        sans-serif;
    position: relative;
    overflow-x: clip;
}
.mv button {
    font: inherit;
    cursor: pointer;
}
.mv svg {
    display: block;
    flex: none;
}

/* Bannière */
.mv-banner {
    position: relative;
    height: 210px;
    overflow: hidden;
    background-color: #2a2230;
    background-size: cover;
    background-position: center;
}
.mv-banner-fade {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(20, 18, 22, 0) 35%,
        var(--mv-bg) 100%
    );
}
.mv-logo {
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
    padding: 6px 10px;
}
.mv-logo img {
    height: 44px;
    width: auto;
    object-fit: contain;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.5));
}
.mv-halo {
    position: absolute;
    left: 50%;
    top: 170px;
    width: 520px;
    height: 220px;
    transform: translateX(-50%);
    background: radial-gradient(
        closest-side,
        rgba(236, 72, 153, 0.22),
        transparent
    );
    pointer-events: none;
    filter: blur(20px);
}

/* Profil */
.mv-wrap {
    max-width: 752px;
    margin: 0 auto;
    padding: 0 16px 110px;
    position: relative;
}
.mv-head {
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-top: -48px;
}
.mv-avatar {
    width: 96px;
    height: 96px;
    border-radius: 999px;
    padding: 3px;
    background: var(--mv-grad);
    position: relative;
    flex: none;
}
.mv-avatar-center {
    margin: 0 auto;
}
.mv-avatar-in {
    width: 100%;
    height: 100%;
    border-radius: 999px;
    border: 3px solid var(--mv-bg);
    background: #3a3040;
    overflow: hidden;
    display: grid;
    place-items: center;
    color: #8a7f8c;
}
.mv-avatar-in img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.mv-avatar-ph {
    width: 44px;
    height: 44px;
}
.mv-online {
    position: absolute;
    right: 4px;
    bottom: 6px;
    width: 15px;
    height: 15px;
    border-radius: 999px;
    background: #22c55e;
    border: 3px solid var(--mv-bg);
    z-index: 2;
}
.mv-livebadge {
    position: absolute;
    left: 50%;
    bottom: -9px;
    transform: translateX(-50%);
    z-index: 3;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.1em;
    padding: 2px 9px;
    border-radius: 999px;
    border: 2px solid var(--mv-bg);
    line-height: 1.5;
}
.mv-head-actions {
    display: flex;
    gap: 8px;
    margin-top: 58px;
}
.mv-btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 999px;
    border: 1px solid var(--mv-line);
    background: var(--mv-card);
    color: var(--mv-text);
}
.mv-btn-msg {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 999px;
    border: 0;
    background: var(--mv-grad);
    color: #fff;
    font-weight: 700;
    font-size: 15px;
    box-shadow: var(--mv-glow);
}
.mv-name {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 14px 0 0;
    font-size: 24px;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.01em;
}
.mv-cert {
    width: 22px;
    height: 22px;
    object-fit: contain;
}
.mv-verified {
    width: 20px;
    height: 20px;
    border-radius: 999px;
    background: var(--mv-grad);
    display: grid;
    place-items: center;
}
.mv-handle {
    color: var(--mv-muted);
    font-size: 14px;
}
.mv-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 18px;
    margin-top: 10px;
    font-size: 13.5px;
    color: var(--mv-muted);
}
.mv-stats span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.mv-stats b {
    color: var(--mv-text);
    font-weight: 700;
    font-size: 14px;
    font-variant-numeric: tabular-nums;
}
.mv-stats svg {
    color: var(--mv-pink);
}
.mv-bio {
    margin: 14px 0 0;
    font-size: 15px;
    font-weight: 600;
}

/* Onglets */
.mv-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    margin-top: 24px;
    border-bottom: 1px solid var(--mv-line);
}
.mv-tabs button {
    position: relative;
    background: none;
    border: 0;
    padding: 12px 8px;
    color: var(--mv-muted);
    font-weight: 600;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.mv-tabs button::after {
    content: '';
    position: absolute;
    left: 18%;
    right: 18%;
    bottom: -1px;
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: var(--mv-grad);
    opacity: 0;
    transition: opacity 0.2s;
}
.mv-tabs button[aria-selected='true'] {
    color: var(--mv-text);
}
.mv-tabs button[aria-selected='true']::after {
    opacity: 1;
}
.mv-reddot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: #ef4444;
    animation: mv-pulse 1.4s infinite;
}
@keyframes mv-pulse {
    50% {
        opacity: 0.6;
    }
}

/* Grille */
.mv-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 16px;
}
.mv-tile {
    position: relative;
    aspect-ratio: 4 / 5;
    background: #2a242e;
    overflow: hidden;
    border-radius: 14px;
    border: 1px solid var(--mv-line);
    padding: 0;
    color: #fff;
    user-select: none;
    -webkit-user-select: none;
}
.mv-tile-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    pointer-events: none;
}
.mv-tile.is-blur .mv-tile-img {
    filter: blur(18px) saturate(1.1);
    transform: scale(1.2);
}
.mv-tile.is-blur::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(10, 8, 12, 0.25);
}
.mv-tile-video {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    background: linear-gradient(160deg, #3b2e3f, #1f1a24);
    color: rgba(255, 255, 255, 0.8);
}
.mv-tile-lock {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    z-index: 2;
}
.mv-tile-lock i {
    width: 38px;
    height: 38px;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.25);
    display: grid;
    place-items: center;
    backdrop-filter: blur(4px);
}
.mv-tile-lock em {
    font-style: normal;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 999px;
    background: var(--mv-grad);
}
.mv-tile-dur {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 2;
    background: rgba(0, 0, 0, 0.65);
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
}
.mv-tile-more {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 2;
    background: rgba(0, 0, 0, 0.6);
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 6px;
}
.mv-tile-dur + .mv-tile-more {
    top: 32px;
}
.mv-tile-live {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 2;
    background: #ef4444;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    padding: 3px 8px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.mv-tile-live i {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: #fff;
    animation: mv-pulse 1.4s ease-in-out infinite;
}
.mv-tile-likes {
    position: absolute;
    left: 8px;
    bottom: 8px;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
}
.mv-tile-likes svg {
    color: var(--mv-rose);
}
.mv-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px 0;
    color: var(--mv-muted);
}

.mv-more {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 24px 0 28px;
}
.mv-more::before,
.mv-more::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(
        to right,
        transparent,
        var(--mv-line),
        transparent
    );
}
.mv-more button {
    background: var(--mv-card);
    border: 1px solid var(--mv-line);
    color: var(--mv-text);
    border-radius: 999px;
    padding: 8px 22px;
    font-weight: 600;
    font-size: 14px;
}
.mv-more button:hover {
    border-color: var(--mv-pink);
}

/* Rencontre */
.mv-meet {
    text-align: center;
    padding: 28px 12px 8px;
}
.mv-meet h3 {
    margin: 22px 0 4px;
    font-size: 20px;
    font-weight: 800;
}
.mv-meet p {
    margin: 0 0 18px;
    color: var(--mv-muted);
    font-size: 14px;
}
.mv-meet .mv-cta {
    max-width: 360px;
    margin: 0 auto 10px;
}
.mv-alt {
    display: block;
    max-width: 360px;
    margin: 0 auto;
    padding: 14px;
    border-radius: 999px;
    background: #fff;
    color: #111;
    font-weight: 700;
    border: 0;
    width: 100%;
}

/* Offre */
.mv-offer {
    position: relative;
    max-width: 340px;
    margin: 0 auto;
    background: var(--mv-card);
    border: 1px solid var(--mv-line);
    border-radius: 18px;
    padding: 24px 20px 20px;
    text-align: center;
    overflow: hidden;
}
.mv-offer::before {
    content: '';
    position: absolute;
    inset: 0 0 auto 0;
    height: 3px;
    background: var(--mv-grad);
}
.mv-offer h3 {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: var(--mv-muted);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.mv-price {
    margin: 16px 0 4px;
    font-size: 56px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.03em;
    font-variant-numeric: tabular-nums;
    display: inline-flex;
    align-items: flex-start;
    gap: 2px;
}
.mv-price span {
    background: var(--mv-grad);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.mv-price small {
    font-size: 20px;
    font-weight: 700;
    color: var(--mv-pink);
    margin-top: 8px;
}
.mv-per {
    font-size: 14px;
    color: var(--mv-muted);
}
.mv-per em {
    font-style: normal;
    color: var(--mv-text);
    font-weight: 600;
}
.mv-was {
    font-size: 12px;
    color: var(--mv-muted);
    text-decoration: line-through;
    margin-top: 4px;
}
.mv-feat {
    list-style: none;
    margin: 18px 0 0;
    padding: 0;
    text-align: left;
    display: grid;
    gap: 10px;
    font-size: 14.5px;
}
.mv-feat li {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.mv-feat i {
    flex: none;
    width: 20px;
    height: 20px;
    border-radius: 6px;
    background: rgba(236, 72, 153, 0.15);
    color: var(--mv-pink);
    display: grid;
    place-items: center;
    margin-top: 1px;
}
.mv-cta {
    margin-top: 22px;
    width: 100%;
    border: 0;
    border-radius: 14px;
    padding: 16px;
    background: var(--mv-grad);
    color: #fff;
    font-weight: 800;
    font-size: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: var(--mv-glow);
    cursor: pointer;
}
.mv-cta-pill {
    border-radius: 999px;
}
.mv-safe {
    margin-top: 12px;
    font-size: 11px;
    color: var(--mv-muted);
    display: flex;
    justify-content: center;
    gap: 6px;
    align-items: center;
}
.mv-foot {
    margin: 40px 0 0;
    text-align: center;
    font-size: 12px;
    color: var(--mv-muted);
}

/* Popup */
.mv-modal {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(8, 6, 10, 0.7);
    backdrop-filter: blur(8px);
    display: grid;
    place-items: center;
    padding: 16px;
    overflow-y: auto;
}
.mv-box {
    position: relative;
    width: 100%;
    max-width: 480px;
    background: var(--mv-card);
    border: 1px solid var(--mv-line);
    border-radius: 22px;
    padding: 34px 24px 26px;
    text-align: center;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
}
.mv-x {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 36px;
    height: 36px;
    border-radius: 999px;
    border: 0;
    background: var(--mv-card2);
    color: #ddd;
    display: grid;
    place-items: center;
}
.mv-box-avatar {
    width: 64px;
    height: 64px;
    border-radius: 999px;
    margin: 0 auto 12px;
    background: #3a3040;
    box-shadow: 0 0 0 3px var(--mv-pink);
    overflow: hidden;
}
.mv-box-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.mv-box h2 {
    margin: 0 0 4px;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.01em;
}
.mv-box-sub {
    color: var(--mv-muted);
    font-size: 14px;
    margin-bottom: 16px;
}
.mv-perks {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 18px;
}
.mv-perks span {
    padding: 9px 6px;
    border-radius: 12px;
    background: var(--mv-card2);
    border: 1px solid var(--mv-line);
    font-size: 12px;
    font-weight: 600;
}
.mv-perks span em {
    display: block;
    font-style: normal;
    font-size: 18px;
    margin-bottom: 2px;
}
.mv-selector {
    text-align: left;
    min-height: 120px;
}
.mv-selector:empty::before {
    content: 'Chargement…';
    display: block;
    padding: 40px 0;
    text-align: center;
    color: var(--mv-muted);
    font-size: 14px;
}

/* Barre flottante */
.mv-sticky {
    position: fixed;
    left: 50%;
    bottom: 14px;
    transform: translateX(-50%);
    width: min(calc(100% - 24px), 560px);
    z-index: 50;
    background: rgba(31, 27, 34, 0.92);
    backdrop-filter: blur(10px);
    border: 1px solid var(--mv-line);
    border-radius: 999px;
    padding: 8px 8px 8px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.mv-sticky-avatar {
    flex: none;
    width: 36px;
    height: 36px;
    border-radius: 999px;
    background: #3a3040;
    border: 2px solid var(--mv-pink);
    overflow: hidden;
}
.mv-sticky-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.mv-sticky-text {
    flex: 1;
    min-width: 0;
    font-size: 13px;
    line-height: 1.3;
}
.mv-sticky-text b {
    display: block;
    font-size: 14px;
}
.mv-sticky-text span {
    color: var(--mv-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}
.mv-sticky-go {
    flex: none;
    border: 0;
    border-radius: 999px;
    padding: 10px 16px;
    background: var(--mv-grad);
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    white-space: nowrap;
}

@media (prefers-reduced-motion: reduce) {
    .mv-reddot,
    .mv-tile-live i {
        animation: none;
    }
}
@media (max-width: 520px) {
    .mv-banner {
        height: 160px;
    }
    .mv-halo {
        top: 120px;
    }
    .mv-price {
        font-size: 48px;
    }
    .mv-grid {
        gap: 6px;
    }
    .mv-tabs button {
        font-size: 14px;
        gap: 6px;
    }
    .mv-sticky-go {
        padding: 10px 12px;
        font-size: 13px;
    }
}

.mv-foot {
    padding-bottom: 100px;
}

/* Bouton flottant Accès premium */
.mv-premium-btn {
    position: fixed;
    left: 50%;
    bottom: calc(18px + env(safe-area-inset-bottom));
    transform: translateX(-50%);
    z-index: 40;
    width: min(92vw, 420px);
    padding: 16px 20px;
    border: 0;
    border-radius: 999px;
    background: var(--mv-grad);
    color: #fff;
    font-weight: 800;
    font-size: 15px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    box-shadow: var(--mv-glow);
    animation: mv-premium-pop 2.4s ease-in-out infinite;
}
@keyframes mv-premium-pop {
    0%,
    100% {
        box-shadow:
            0 10px 30px rgba(244, 63, 94, 0.28),
            0 0 0 0 rgba(244, 63, 94, 0.45);
    }
    50% {
        box-shadow:
            0 10px 30px rgba(244, 63, 94, 0.28),
            0 0 0 12px rgba(244, 63, 94, 0);
    }
}
@media (prefers-reduced-motion: reduce) {
    .mv-premium-btn {
        animation: none;
    }
}

/* Popup des services */
.mv-premium {
    backdrop-filter: blur(8px);
}
.mv-premium-box {
    position: relative;
    width: min(92vw, 380px);
    padding: 26px 22px 20px;
    border-radius: 22px;
    background: var(--mv-card);
    border: 1px solid var(--mv-line);
    text-align: center;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5);
    animation: mv-premium-in 0.25s ease-out;
}
@keyframes mv-premium-in {
    from {
        transform: translateY(16px) scale(0.97);
    }
    to {
        transform: none;
    }
}
.mv-premium-tag {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 999px;
    background: rgba(244, 63, 94, 0.15);
    color: var(--mv-rose);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.mv-premium-box h2 {
    margin: 12px 0 8px;
    font-size: 22px;
    font-weight: 800;
}
.mv-premium-trial {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    background: var(--mv-rose);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}
.mv-premium-price {
    display: flex;
    justify-content: center;
    align-items: baseline;
    gap: 10px;
    margin-top: 8px;
}
.mv-premium-price s {
    color: var(--mv-muted);
    font-size: 15px;
}
.mv-premium-price b {
    color: var(--mv-rose);
    font-size: 30px;
    font-weight: 800;
}
.mv-premium-renew {
    margin: 2px 0 14px;
    color: var(--mv-muted);
    font-size: 12px;
}
.mv-premium-list {
    margin: 0 0 18px;
    padding: 0;
    list-style: none;
    text-align: left;
}
.mv-premium-list li {
    position: relative;
    padding: 6px 0 6px 18px;
    font-size: 14px;
}
.mv-premium-list li::before {
    content: '';
    position: absolute;
    left: 2px;
    top: 13px;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--mv-rose);
}
.mv-premium-go {
    width: 100%;
    padding: 15px;
    border: 0;
    border-radius: 14px;
    background: var(--mv-grad);
    color: #fff;
    font-weight: 800;
    font-size: 16px;
    box-shadow: var(--mv-glow);
}
.mv-premium-legal {
    margin: 12px 0 0;
    color: var(--mv-muted);
    font-size: 11px;
}
</style>
