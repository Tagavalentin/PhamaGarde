<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    nom: '',
    adresse: '',
    quartier: '',
    ville: '',
    latitude: 6.1250,
    longitude: 1.2167,
    telephone: '',
});

const mapContainer = ref(null);
let map = null;
let marker = null;

onMounted(() => {
    initMap();
});

function initMap() {
    if (!mapContainer.value) return;

    map = L.map(mapContainer.value).setView([6.1250, 1.2167], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    marker = L.marker([6.1250, 1.2167], {
        draggable: true
    }).addTo(map);

    marker.on('dragend', function(e) {
        const position = e.target.getLatLng();
        form.latitude = position.lat;
        form.longitude = position.lng;
    });

    map.on('click', function(e) {
        const position = e.latlng;
        marker.setLatLng(position);
        form.latitude = position.lat;
        form.longitude = position.lng;
    });
}

function useMyPosition() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                form.latitude = lat;
                form.longitude = lng;
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], 15);
            },
            (error) => {
                alert('Impossible d\'obtenir votre position');
            }
        );
    } else {
        alert('La géolocalisation n\'est pas supportée par votre navigateur');
    }
}

function submit() {
    form.post('/inscription-pharmacien');
}
</script>

<template>
    <Head title="Inscription Pharmacien" />

    <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Inscription Pharmacien
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Créez votre compte et votre fiche pharmacie
                </p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Informations personnelles -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Informations personnelles</h3>
                        
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nom complet</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Téléphone</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
                        </div>
                    </div>

                    <!-- Informations pharmacie -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Informations pharmacie</h3>
                        
                        <div>
                            <label for="nom" class="block text-sm font-medium text-gray-700">Nom de la pharmacie</label>
                            <input
                                id="nom"
                                v-model="form.nom"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.nom" class="text-red-500 text-sm mt-1">{{ form.errors.nom }}</div>
                        </div>

                        <div>
                            <label for="adresse" class="block text-sm font-medium text-gray-700">Adresse</label>
                            <input
                                id="adresse"
                                v-model="form.adresse"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.adresse" class="text-red-500 text-sm mt-1">{{ form.errors.adresse }}</div>
                        </div>

                        <div>
                            <label for="quartier" class="block text-sm font-medium text-gray-700">Quartier</label>
                            <input
                                id="quartier"
                                v-model="form.quartier"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.quartier" class="text-red-500 text-sm mt-1">{{ form.errors.quartier }}</div>
                        </div>

                        <div>
                            <label for="ville" class="block text-sm font-medium text-gray-700">Ville</label>
                            <input
                                id="ville"
                                v-model="form.ville"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.ville" class="text-red-500 text-sm mt-1">{{ form.errors.ville }}</div>
                        </div>

                        <div>
                            <label for="telephone" class="block text-sm font-medium text-gray-700">Téléphone de la pharmacie</label>
                            <input
                                id="telephone"
                                v-model="form.telephone"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.telephone" class="text-red-500 text-sm mt-1">{{ form.errors.telephone }}</div>
                        </div>
                    </div>
                </div>

                <!-- Carte pour sélectionner la position -->
                <div class="space-y-2">
                    <h3 class="text-lg font-medium text-gray-900">Position de la pharmacie</h3>
                    <p class="text-sm text-gray-600">Cliquez sur la carte ou déplacez le marqueur pour sélectionner la position</p>
                    <button
                        type="button"
                        @click="useMyPosition"
                        class="mb-2 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        Utiliser ma position
                    </button>
                    <div ref="mapContainer" class="h-96 w-full rounded-lg border border-gray-300"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Latitude</label>
                            <input
                                v-model="form.latitude"
                                type="number"
                                step="any"
                                readonly
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Longitude</label>
                            <input
                                v-model="form.longitude"
                                type="number"
                                step="any"
                                readonly
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                            />
                        </div>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        S'inscrire
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
