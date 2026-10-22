<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const hours = ref({});
const loading = ref(false);

const days = [
    { id: 1, name: 'Lundi' },
    { id: 2, name: 'Mardi' },
    { id: 3, name: 'Mercredi' },
    { id: 4, name: 'Jeudi' },
    { id: 5, name: 'Vendredi' },
    { id: 6, name: 'Samedi' },
    { id: 7, name: 'Dimanche' },
];

onMounted(() => {
    loadHours();
});

async function loadHours() {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/api/pharma/my-pharmacy/hours', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        hours.value = await response.json();
    } catch (error) {
        console.error('Error loading hours:', error);
    } finally {
        loading.value = false;
    }
}

function initHours() {
    for (let i = 1; i <= 7; i++) {
        if (!hours.value[i] || hours.value[i].length === 0) {
            hours.value[i] = [{ jour_semaine: i, heure_ouverture: '', heure_fermeture: '' }];
        }
    }
}

function addSlot(dayId) {
    if (!hours.value[dayId]) {
        hours.value[dayId] = [];
    }
    hours.value[dayId].push({
        jour_semaine: dayId,
        heure_ouverture: '',
        heure_fermeture: ''
    });
}

function removeSlot(dayId, index) {
    hours.value[dayId].splice(index, 1);
    if (hours.value[dayId].length === 0) {
        hours.value[dayId] = [{ jour_semaine: dayId, heure_ouverture: '', heure_fermeture: '' }];
    }
}

function copyToAll(sourceDayId) {
    const sourceHours = hours.value[sourceDayId];
    for (let i = 1; i <= 7; i++) {
        if (i !== sourceDayId) {
            hours.value[i] = JSON.parse(JSON.stringify(sourceHours)).map(h => ({
                ...h,
                jour_semaine: i
            }));
        }
    }
}

async function save() {
    try {
        const token = localStorage.getItem('token');
        const flatHours = Object.values(hours.value).flat();
        
        const response = await fetch('/api/pharma/my-pharmacy/hours', {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ hours: flatHours })
        });

        if (response.ok) {
            alert('Horaires enregistrés');
        } else {
            alert('Erreur lors de l\'enregistrement');
        }
    } catch (error) {
        alert('Erreur lors de l\'enregistrement');
    }
}
</script>

<template>
    <Head title="Mes horaires" />

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Mes horaires</h1>

            <div v-if="loading" class="text-center py-12">
                <p class="text-gray-500">Chargement...</p>
            </div>

            <div v-else>
                <div v-for="day in days" :key="day.id" class="bg-white shadow rounded-lg p-6 mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ day.name }}</h3>
                        <button
                            @click="copyToAll(day.id)"
                            class="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            Copier sur tous les jours
                        </button>
                    </div>

                    <div
                        v-for="(slot, index) in hours[day.id] || []"
                        :key="index"
                        class="flex items-center space-x-4 mb-2"
                    >
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ouverture</label>
                            <input
                                v-model="slot.heure_ouverture"
                                type="time"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fermeture</label>
                            <input
                                v-model="slot.heure_fermeture"
                                type="time"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <button
                            @click="removeSlot(day.id, index)"
                            class="mt-6 text-red-600 hover:text-red-800"
                        >
                            Supprimer
                        </button>
                    </div>

                    <button
                        @click="addSlot(day.id)"
                        class="mt-2 text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        + Ajouter une plage horaire
                    </button>
                </div>

                <div class="flex justify-end">
                    <button
                        @click="save"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
