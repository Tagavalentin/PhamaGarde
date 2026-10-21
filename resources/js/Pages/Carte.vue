<script setup>
import { ref, onMounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const map = ref(null);
const pharmacies = ref([]);
const filters = ref({
    open: false,
    garde: false,
});

const fetchPharmacies = async () => {
    try {
        const params = new URLSearchParams();
        if (filters.value.open) params.append('open', '1');
        if (filters.value.garde) params.append('garde', '1');

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

const updateMarkers = () => {
    if (!map.value) return;

    map.value.eachLayer((layer) => {
        if (layer instanceof L.Marker) {
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

        marker.bindPopup(`
            <div style="min-width: 200px;">
                <h3 style="margin: 0 0 8px 0; font-size: 16px;">${pharmacy.nom}</h3>
                <p style="margin: 4px 0; font-size: 14px;"><strong>Statut:</strong> ${pharmacy.statut}</p>
                <p style="margin: 4px 0; font-size: 14px;"><strong>Téléphone:</strong> <a href="tel:${pharmacy.telephone}">${pharmacy.telephone}</a></p>
                <p style="margin: 4px 0; font-size: 14px;"><strong>Adresse:</strong> ${pharmacy.adresse}, ${pharmacy.quartier}</p>
            </div>
        `);

        marker.addTo(map.value);
    });
};

const applyFilters = () => {
    fetchPharmacies();
};

onMounted(() => {
    map.value = L.map('map').setView([6.1250, 1.2167], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map.value);

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
            </div>
        </div>

        <div id="map" class="h-[calc(100vh-140px)] w-full"></div>
    </div>
</template>

<style scoped>
.custom-marker {
    background: transparent;
}
</style>
