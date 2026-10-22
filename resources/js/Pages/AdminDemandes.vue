<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const pharmacies = ref([]);

onMounted(async () => {
    const response = await fetch('/api/pharma/admin/pharmacies/pending', {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
        }
    });
    pharmacies.value = await response.json();
});

async function approve(id) {
    if (!confirm('Valider cette pharmacie ?')) return;

    const response = await fetch(`/api/pharma/admin/pharmacies/${id}/approve`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
        }
    });

    if (response.ok) {
        pharmacies.value = pharmacies.value.filter(p => p.id !== id);
        alert('Pharmacie validée');
    }
}

async function reject(id) {
    const motif = prompt('Motif du rejet :');
    if (!motif) return;

    const response = await fetch(`/api/pharma/admin/pharmacies/${id}/reject`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ motif })
    });

    if (response.ok) {
        pharmacies.value = pharmacies.value.filter(p => p.id !== id);
        alert('Pharmacie rejetée');
    }
}
</script>

<template>
    <Head title="Demandes en attente" />

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Demandes en attente</h1>

            <div v-if="pharmacies.length === 0" class="text-center py-12">
                <p class="text-gray-500">Aucune demande en attente</p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="pharmacy in pharmacies"
                    :key="pharmacy.id"
                    class="bg-white shadow rounded-lg p-6"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ pharmacy.nom }}</h3>
                            <p class="text-sm text-gray-600">{{ pharmacy.adresse }}</p>
                            <p class="text-sm text-gray-600">{{ pharmacy.quartier }}, {{ pharmacy.ville }}</p>
                            <p class="text-sm text-gray-600 mt-2">
                                <span class="font-medium">Téléphone :</span> {{ pharmacy.telephone }}
                            </p>
                            <p class="text-sm text-gray-600">
                                <span class="font-medium">Position :</span> {{ pharmacy.latitude }}, {{ pharmacy.longitude }}
                            </p>
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-2">Pharmacien</h4>
                            <p class="text-sm text-gray-600">{{ pharmacy.user?.name }}</p>
                            <p class="text-sm text-gray-600">{{ pharmacy.user?.email }}</p>
                            <p class="text-sm text-gray-600">{{ pharmacy.user?.phone }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex space-x-4">
                        <button
                            @click="approve(pharmacy.id)"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                        >
                            Valider
                        </button>
                        <button
                            @click="reject(pharmacy.id)"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                        >
                            Refuser
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
