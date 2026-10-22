<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import MedicineSearch from '../Components/MedicineSearch.vue';

const page = usePage();
const user = page.props.auth?.user;

const stocks = ref([]);
const search = ref('');
const loading = ref(false);

onMounted(() => {
    loadStocks();
});

async function loadStocks() {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const url = search.value 
            ? `/api/pharma/my-pharmacy/stock?search=${encodeURIComponent(search.value)}`
            : '/api/pharma/my-pharmacy/stock';
        
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        stocks.value = await response.json();
    } catch (error) {
        console.error('Error loading stocks:', error);
    } finally {
        loading.value = false;
    }
}

async function updateStock(medicineId, statut) {
    const stock = stocks.value.data.find(s => s.medicine_id === medicineId);
    if (!stock) return;

    const previousStatut = stock.statut;
    stock.statut = statut;

    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/api/pharma/my-pharmacy/stock/${medicineId}`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ statut })
        });

        if (!response.ok) {
            stock.statut = previousStatut;
            alert('Erreur lors de la mise à jour');
        }
    } catch (error) {
        stock.statut = previousStatut;
        alert('Erreur lors de la mise à jour');
    }
}

async function selectMedicine(medicine) {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/api/pharma/my-pharmacy/stock/${medicine.id}`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ statut: 'en stock' })
        });

        if (response.ok) {
            await loadStocks();
        }
    } catch (error) {
        alert('Erreur lors de l\'ajout');
    }
}

function getStatutColor(statut) {
    switch (statut) {
        case 'en stock': return 'bg-green-100 text-green-800';
        case 'stock faible': return 'bg-yellow-100 text-yellow-800';
        case 'rupture': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffHours / 24);

    if (diffHours < 1) return 'Il y a moins d\'une heure';
    if (diffHours < 24) return `Il y a ${diffHours} h`;
    if (diffDays === 1) return 'Hier';
    if (diffDays < 7) return `Il y a ${diffDays} jours`;
    return date.toLocaleDateString('fr-FR');
}
</script>

<template>
    <Head title="Mon stock" />

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Mon stock</h1>

            <!-- Recherche -->
            <div class="mb-6">
                <input
                    v-model="search"
                    @input="loadStocks"
                    type="text"
                    placeholder="Rechercher dans mon stock..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
            </div>

            <!-- Ajout rapide -->
            <div class="mb-6 bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Ajouter un médicament</h2>
                <MedicineSearch @select="selectMedicine" />
            </div>

            <!-- Liste du stock -->
            <div v-if="loading" class="text-center py-12">
                <p class="text-gray-500">Chargement...</p>
            </div>

            <div v-else-if="stocks.data && stocks.data.length === 0" class="text-center py-12">
                <p class="text-gray-500">Aucun médicament dans votre stock</p>
            </div>

            <div v-else class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Médicament
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantité
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Prix
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Mis à jour
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="stock in stocks.data" :key="stock.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ stock.medicine.nom_commercial }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ stock.medicine.dci }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatutColor(stock.statut)">
                                    {{ stock.statut }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ stock.quantite || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ stock.prix ? stock.prix + ' FCFA' : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(stock.updated_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button
                                    @click="updateStock(stock.medicine_id, 'en stock')"
                                    :class="stock.statut === 'en stock' ? 'bg-green-600' : 'bg-green-100 text-green-800 hover:bg-green-200'"
                                    class="px-3 py-1 rounded-md"
                                >
                                    En stock
                                </button>
                                <button
                                    @click="updateStock(stock.medicine_id, 'stock faible')"
                                    :class="stock.statut === 'stock faible' ? 'bg-yellow-600 text-white' : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'"
                                    class="px-3 py-1 rounded-md"
                                >
                                    Stock faible
                                </button>
                                <button
                                    @click="updateStock(stock.medicine_id, 'rupture')"
                                    :class="stock.statut === 'rupture' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-800 hover:bg-red-200'"
                                    class="px-3 py-1 rounded-md"
                                >
                                    Rupture
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="stocks.links" class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Précédent
                        </a>
                        <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Suivant
                        </a>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Affichage de <span class="font-medium">{{ stocks.from || 0 }}</span> à <span class="font-medium">{{ stocks.to || 0 }}</span> sur <span class="font-medium">{{ stocks.total || 0 }}</span> résultats
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
