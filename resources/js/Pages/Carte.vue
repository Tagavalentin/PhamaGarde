<script setup>
import { ref, onMounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useGeolocation } from '../Composables/useGeolocation';

const map = ref(null);
const pharmacies = ref([]);
const userMarker = ref(null);
const filters = ref({
    open: false,
    garde: false,
});

const { location, error, isLoading, getUserLocation, LOME_DEFAULT } = useGeolocation();

const fetchPharmacies = async () => {
    try {
        const params = new URLSearchParams();
        if (filters.value.open) params.append('open', '1');
        if (filters.value.garde) params.append('garde', '1');
        if (location.value) {
            params.append('lat', location.value.lat);
            params.append('lng', location.value.lng);
        }

        const response = await fetch(`/api/pharma/pharmacies?${params.toString()}`);
        const data = await response.json();
        pharmacies.value = data;
        updateMarkers();
    } catch (error) {
        console.error('Erreur lors de la récupération des pharmacies:', error);
    }
};

const getMarkerColor = (statut) => {
    switch (statut) {
        case 'ouvert':
            return '#22c55e';
        case 'ferme':
            return '#6b7280';
        case 'garde':
            return '#ef4444';
        default:
            return '#6b7280';
    }
};

const updateUserMarker = () => {
    if (!map.value) return;

    if (userMarker.value) {
        map.value.removeLayer(userMarker.value);
    }

    if (location.value) {
        const userIcon = L.divIcon({
            className: 'user-marker',
            html: `
                <div style="
                    background-color: #3b82f6;
                    width: 20px;
                    height: 20px;
                    border-radius: 50%;
                    border: 3px solid white;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                    position: relative;
                ">
                    <div style="
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        color: white;
                        font-size: 10px;
                        font-weight: bold;
                    ">+</div>
                </div>
            `,
            iconSize: [20, 20],
            iconAnchor: [10, 10],
        });

        userMarker.value = L.marker([location.value.lat, location.value.lng], {
            icon: userIcon,
        });

        userMarker.value.bindPopup('Vous êtes ici');
        userMarker.value.addTo(map.value);

        map.value.setView([location.value.lat, location.value.lng], 13);
    }
};

const updateMarkers = () => {
    if (!map.value) return;

    map.value.eachLayer((layer) => {
        if (layer instanceof L.Marker && layer !== userMarker.value) {
            map.value.removeLayer(layer);
        }
    });

    pharmacies.value.forEach((pharmacy) => {
        const marker = L.marker([pharmacy.latitude, pharmacy.longitude], {
            icon: L.divIcon({
                className: 'custom-marker',
                html: `<div style="background-color: ${getMarkerColor(pharmacy.statut)}; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15],
            }),
        });

        const popupContent = `
            <div style="min-width: 200px;">
                <h3 style="margin: 0 0 8px 0; font-size: 16px;">${pharmacy.nom}</h3>
                <p style="margin: 4px 0; font-size: 14px;"><strong>Statut:</strong> ${pharmacy.statut}</p>
                ${pharmacy.distance_km ? `<p style="margin: 4px 0; font-size: 14px;"><strong>Distance:</strong> ${pharmacy.distance_km} km</p>` : ''}
                <p style="margin: 4px 0; font-size: 14px;"><strong>Téléphone:</strong> <a href="tel:${pharmacy.telephone}">${pharmacy.telephone}</a></p>
                <p style="margin: 4px 0; font-size: 14px;"><strong>Adresse:</strong> ${pharmacy.adresse}, ${pharmacy.quartier}</p>
            </div>
        `;

        marker.bindPopup(popupContent);
        marker.addTo(map.value);
    });
};

const applyFilters = () => {
    fetchPharmacies();
};

onMounted(async () => {
    map.value = L.map('map').setView([LOME_DEFAULT.lat, LOME_DEFAULT.lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map.value);

    await getUserLocation();
    updateUserMarker();
    fetchPharmacies();
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="bg-white shadow-md p-4">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Carte des Pharmacies</h1>
            
            <div class="flex flex-wrap gap-4 mb-4">
                <button
                    @click="filters.open = !filters.open; filters.garde = false; applyFilters()"
                    :class="filters.open ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-800'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors"
                >
                    {{ filters.open ? '✓ ' : '' }}Ouvertes maintenant
                </button>
                
                <button
                    @click="filters.garde = !filters.garde; filters.open = false; applyFilters()"
                    :class="filters.garde ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-800'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors"
                >
                    {{ filters.garde ? '✓ ' : '' }}De garde ce soir
                </button>
                
                <button
                    v-if="filters.open || filters.garde"
                    @click="filters.open = false; filters.garde = false; applyFilters()"
                    class="px-4 py-2 rounded-lg font-medium bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors"
                >
                    Réinitialiser
                </button>
            </div>

            <div class="flex gap-6 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-green-500"></div>
                    <span>Ouvert</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-gray-500"></div>
                    <span>Fermé</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-red-500"></div>
                    <span>De garde</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                    <span>Vous êtes ici</span>
                </div>
            </div>

            <div v-if="error" class="mt-4 p-3 bg-yellow-100 border border-yellow-300 rounded-lg text-sm text-yellow-800">
                {{ error }}. Position par défaut utilisée (Lomé).
            </div>
        </div>

        <div id="map" class="h-[500px] w-full"></div>

        <div class="bg-white shadow-md p-4">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Liste des pharmacies</h2>
            <div v-if="pharmacies.length === 0" class="text-gray-600">
                Aucune pharmacie trouvée.
            </div>
            <div v-else class="space-y-3">
                <div
                    v-for="pharmacy in pharmacies"
                    :key="pharmacy.id"
                    class="border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ pharmacy.nom }}</h3>
                            <p class="text-sm text-gray-600">{{ pharmacy.adresse }}, {{ pharmacy.quartier }}</p>
                            <p class="text-sm text-gray-600">
                                <span :class="{
                                    'text-green-600': pharmacy.statut === 'ouvert',
                                    'text-gray-600': pharmacy.statut === 'ferme',
                                    'text-red-600': pharmacy.statut === 'garde'
                                }" class="font-medium capitalize">
                                    {{ pharmacy.statut }}
                                </span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p v-if="pharmacy.distance_km" class="text-lg font-bold text-blue-600">
                                {{ pharmacy.distance_km }} km
                            </p>
                            <a :href="`tel:${pharmacy.telephone}`" class="text-sm text-green-600 hover:underline">
                                {{ pharmacy.telephone }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-marker {
    background: transparent;
}
</style>
