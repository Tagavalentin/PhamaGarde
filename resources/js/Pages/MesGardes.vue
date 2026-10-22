<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const duties = ref([]);
const loading = ref(false);
const showForm = ref(false);

const newDuty = ref({
    date_debut: '',
    date_fin: '',
});

onMounted(() => {
    loadDuties();
});

async function loadDuties() {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/api/pharma/my-pharmacy/duty', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        duties.value = await response.json();
    } catch (error) {
        console.error('Error loading duties:', error);
    } finally {
        loading.value = false;
    }
}

async function addDuty() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/api/pharma/my-pharmacy/duty', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(newDuty.value)
        });

        if (response.ok) {
            await loadDuties();
            showForm.value = false;
            newDuty.value = { date_debut: '', date_fin: '' };
        } else {
            const data = await response.json();
            alert(data.message || 'Erreur lors de l\'ajout');
        }
    } catch (error) {
        alert('Erreur lors de l\'ajout');
    }
}

async function deleteDuty(id) {
    if (!confirm('Supprimer cette garde ?')) return;

    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/api/pharma/my-pharmacy/duty/${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            await loadDuties();
        } else {
            alert('Erreur lors de la suppression');
        }
    } catch (error) {
        alert('Erreur lors de la suppression');
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}
</script>

<template>
    <Head title="Mes gardes" />

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Mes gardes</h1>
                <button
                    @click="showForm = true"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    Ajouter une garde
                </button>
            </div>

            <!-- Formulaire d'ajout -->
            <div v-if="showForm" class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Nouvelle garde</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                        <input
                            v-model="newDuty.date_debut"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                        <input
                            v-model="newDuty.date_fin"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                </div>
                <div class="mt-4 flex space-x-4">
                    <button
                        @click="addDuty"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                    >
                        Enregistrer
                    </button>
                    <button
                        @click="showForm = false; newDuty = { date_debut: '', date_fin: '' }"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                    >
                        Annuler
                    </button>
                </div>
            </div>

            <!-- Liste des gardes -->
            <div v-if="loading" class="text-center py-12">
                <p class="text-gray-500">Chargement...</p>
            </div>

            <div v-else-if="duties.length === 0" class="text-center py-12">
                <p class="text-gray-500">Aucune garde programmée</p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="duty in duties"
                    :key="duty.id"
                    class="bg-white shadow rounded-lg p-6"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-lg font-medium text-gray-900">
                                Du {{ formatDate(duty.date_debut) }} au {{ formatDate(duty.date_fin) }}
                            </p>
                        </div>
                        <button
                            @click="deleteDuty(duty.id)"
                            class="text-red-600 hover:text-red-800"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
