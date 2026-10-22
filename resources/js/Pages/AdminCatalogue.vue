<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const medicines = ref([]);
const search = ref('');
const loading = ref(false);
const showImportForm = ref(false);
const importFile = ref(null);
const importResult = ref(null);

onMounted(() => {
    loadMedicines();
});

async function loadMedicines() {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const url = search.value 
            ? `/api/pharma/medicines?q=${encodeURIComponent(search.value)}`
            : '/api/pharma/medicines';
        
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        medicines.value = await response.json();
    } catch (error) {
        console.error('Error loading medicines:', error);
    } finally {
        loading.value = false;
    }
}

async function importCSV() {
    if (!importFile.value) {
        alert('Veuillez sélectionner un fichier CSV');
        return;
    }

    const formData = new FormData();
    formData.append('csv', importFile.value);

    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/api/pharma/admin/medicines/import', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();
        
        if (response.ok) {
            importResult.value = data;
            await loadMedicines();
            showImportForm.value = false;
            importFile.value = null;
        } else {
            alert(data.message || 'Erreur lors de l\'import');
        }
    } catch (error) {
        alert('Erreur lors de l\'import');
    }
}

function handleFileChange(event) {
    importFile.value = event.target.files[0];
}
</script>

<template>
    <Head title="Catalogue de médicaments" />

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Catalogue de médicaments</h1>
                <button
                    @click="showImportForm = true"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                >
                    Importer CSV
                </button>
            </div>

            <!-- Formulaire d'import -->
            <div v-if="showImportForm" class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Importer un fichier CSV</h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fichier CSV (max 2 Mo)</label>
                    <input
                        type="file"
                        accept=".csv,.txt"
                        @change="handleFileChange"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    />
                    <p class="mt-2 text-sm text-gray-500">
                        Colonnes requises : nom_commercial, dci, forme, dosage, categorie
                    </p>
                </div>
                <div class="flex space-x-4">
                    <button
                        @click="importCSV"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                    >
                        Importer
                    </button>
                    <button
                        @click="showImportForm = false; importFile = null; importResult = null"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                    >
                        Annuler
                    </button>
                </div>

                <!-- Résultat de l'import -->
                <div v-if="importResult" class="mt-4 p-4 bg-gray-100 rounded-md">
                    <p><strong>Importés :</strong> {{ importResult.imported }}</p>
                    <p><strong>Mis à jour :</strong> {{ importResult.updated }}</p>
                    <p><strong>Rejetés :</strong> {{ importResult.rejected.length }}</p>
                    <div v-if="importResult.rejected.length > 0" class="mt-2">
                        <details>
                            <summary class="cursor-pointer text-sm text-red-600">Voir les rejets</summary>
                            <ul class="mt-2 text-sm">
                                <li v-for="(rejection, index) in importResult.rejected" :key="index">
                                    Ligne {{ rejection.line }}: {{ rejection.reason }}
                                </li>
                            </ul>
                        </details>
                    </div>
                </div>
            </div>

            <!-- Recherche -->
            <div class="mb-6">
                <input
                    v-model="search"
                    @input="loadMedicines"
                    type="text"
                    placeholder="Rechercher un médicament..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
            </div>

            <!-- Liste des médicaments -->
            <div v-if="loading" class="text-center py-12">
                <p class="text-gray-500">Chargement...</p>
            </div>

            <div v-else-if="medicines.length === 0" class="text-center py-12">
                <p class="text-gray-500">Aucun médicament trouvé</p>
            </div>

            <div v-else class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nom commercial
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                DCI
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Forme
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Dosage
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Catégorie
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="medicine in medicines" :key="medicine.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ medicine.nom_commercial }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ medicine.dci }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ medicine.forme || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ medicine.dosage || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ medicine.categorie || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
