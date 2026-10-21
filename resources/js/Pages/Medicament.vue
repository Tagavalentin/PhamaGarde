<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    id: {
        type: Number,
        required: true,
    },
});

const medicine = ref(null);
const pharmacies = ref([]);
const isLoading = ref(true);
const userLocation = ref(null);
const exclureRupture = ref(false);

const fetchMedicine = async () => {
    try {
        const response = await fetch(`/api/pharma/medicines/${props.id}`);
        const data = await response.json();
        medicine.value = data;
    } catch (error) {
        console.error('Erreur lors de la récupération du médicament:', error);
    }
};

const fetchAvailability = async () => {
    try {
        let url = `/api/pharma/medicines/${props.id}/availability`;
        const params = new URLSearchParams();
        
        if (userLocation.value) {
            params.append('lat', userLocation.value.lat);
            params.append('lng', userLocation.value.lng);
        }
        
        if (exclureRupture.value) {
            params.append('exclure_rupture', '1');
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }

        const response = await fetch(url);
        const data = await response.json();
        pharmacies.value = data;
    } catch (error) {
        console.error('Erreur lors de la récupération de la disponibilité:', error);
    } finally {
        isLoading.value = false;
    }
};

const getStockStatusColor = (statut) => {
    switch (statut) {
        case 'en stock':
            return 'bg-green-500';
        case 'stock faible':
            return 'bg-orange-500';
        case 'rupture':
            return 'bg-red-500';
        default:
            return 'bg-gray-500';
    }
};

const getOpeningStatusColor = (statut) => {
    switch (statut) {
        case 'ouvert':
            return 'text-green-600';
        case 'ferme':
            return 'text-gray-600';
        case 'garde':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
};

const getTimeSinceUpdate = (updatedAt) => {
    const now = new Date();
    const updated = new Date(updatedAt);
    const diffInHours = Math.floor((now - updated) / (1000 * 60 * 60));
    
    if (diffInHours < 1) return 'moins d\'une heure';
    if (diffInHours === 1) return '1 heure';
    if (diffInHours < 24) return `${diffInHours} heures`;
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays === 1) return '1 jour';
    return `${diffInDays} jours`;
};

const getUserLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation.value = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                fetchAvailability();
            },
            (error) => {
                console.log('Localisation non disponible:', error);
                fetchAvailability();
            }
        );
    } else {
        fetchAvailability();
    }
};

const toggleExclureRupture = () => {
    exclureRupture.value = !exclureRupture.value;
    fetchAvailability();
};

onMounted(() => {
    fetchMedicine();
    getUserLocation();
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Informations indicatives, appelez la pharmacie pour confirmer la disponibilité.
                    </p>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 py-8">
            <div v-if="isLoading" class="text-center py-8">
                <div class="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                <p class="mt-4 text-gray-600">Chargement...</p>
            </div>

            <div v-else>
                <div v-if="medicine" class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ medicine.nom_commercial }}</h1>
                    <p class="text-gray-600 mb-2"><strong>DCI:</strong> {{ medicine.dci }}</p>
                    <p class="text-gray-600 mb-2"><strong>Forme:</strong> {{ medicine.forme }}</p>
                    <p class="text-gray-600"><strong>Dosage:</strong> {{ medicine.dosage }}</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Pharmacies disponibles</h2>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="exclureRupture"
                                @change="toggleExclureRupture"
                                class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                            />
                            <span class="text-sm text-gray-700">Exclure les ruptures</span>
                        </label>
                    </div>

                    <div v-if="pharmacies.length === 0" class="text-center py-8 text-gray-600">
                        Aucune pharmacie trouvée pour ce médicament.
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="pharmacy in pharmacies"
                            :key="pharmacy.pharmacy_id"
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-800">{{ pharmacy.nom }}</h3>
                                        <div
                                            :class="getStockStatusColor(pharmacy.statut_stock)"
                                            class="w-3 h-3 rounded-full"
                                        ></div>
                                        <span class="text-sm text-gray-600 capitalize">{{ pharmacy.statut_stock }}</span>
                                        <span
                                            v-if="pharmacy.a_confirmer"
                                            class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full"
                                        >
                                            À confirmer
                                        </span>
                                    </div>

                                    <p class="text-sm text-gray-600 mb-1">
                                        {{ pharmacy.adresse }}, {{ pharmacy.quartier }}
                                    </p>
                                    <p class="text-sm text-gray-600 mb-1">
                                        {{ pharmacy.ville }}
                                    </p>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <strong>Statut:</strong>
                                        <span :class="getOpeningStatusColor(pharmacy.statut_ouverture)" class="font-medium capitalize">
                                            {{ pharmacy.statut_ouverture }}
                                        </span>
                                    </p>
                                    <p class="text-sm text-gray-500 mb-2">
                                        Mis à jour il y a {{ getTimeSinceUpdate(pharmacy.updated_at) }}
                                    </p>
                                    <p v-if="pharmacy.distance_km" class="text-sm text-gray-600">
                                        <strong>Distance:</strong> {{ pharmacy.distance_km.toFixed(1) }} km
                                    </p>
                                </div>

                                <div class="flex flex-col gap-2 ml-4">
                                    <a
                                        :href="`tel:${pharmacy.telephone}`"
                                        class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors text-center"
                                    >
                                        Appeler
                                    </a>
                                    <a
                                        :href="`/pharmacies/${pharmacy.pharmacy_id}`"
                                        class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors text-center"
                                    >
                                        Voir la fiche
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
