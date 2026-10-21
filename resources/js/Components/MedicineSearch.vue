<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const searchQuery = ref('');
const suggestions = ref([]);
const isLoading = ref(false);
const selectedIndex = ref(-1);
const showSuggestions = ref(false);
const abortController = ref(null);

const fetchSuggestions = async () => {
    if (searchQuery.value.length < 2) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    if (abortController.value) {
        abortController.value.abort();
    }

    abortController.value = new AbortController();
    isLoading.value = true;

    try {
        const response = await fetch(`/api/pharma/medicines?q=${encodeURIComponent(searchQuery.value)}`, {
            signal: abortController.value.signal,
        });

        if (response.ok) {
            const data = await response.json();
            suggestions.value = data;
            showSuggestions.value = data.length > 0;
            selectedIndex.value = -1;
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.error('Erreur lors de la recherche:', error);
        }
    } finally {
        isLoading.value = false;
    }
};

const selectMedicine = (medicine) => {
    showSuggestions.value = false;
    searchQuery.value = '';
    suggestions.value = [];
    router.push(`/disponibilite/${medicine.id}`);
};

const handleKeyDown = (event) => {
    if (!showSuggestions.value || suggestions.value.length === 0) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectedIndex.value = (selectedIndex.value + 1) % suggestions.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectedIndex.value = selectedIndex.value <= 0 ? suggestions.value.length - 1 : selectedIndex.value - 1;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        if (selectedIndex.value >= 0) {
            selectMedicine(suggestions.value[selectedIndex.value]);
        }
    } else if (event.key === 'Escape') {
        showSuggestions.value = false;
        selectedIndex.value = -1;
    }
};

const hideSuggestions = () => {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
};

// Debounce
let debounceTimer;
watch(searchQuery, (newValue) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchSuggestions();
    }, 300);
});
</script>

<template>
    <div class="relative w-full max-w-xl mx-auto">
        <div class="relative">
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher un médicament..."
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none"
                @keydown="handleKeyDown"
                @focus="showSuggestions = suggestions.length > 0"
                @blur="hideSuggestions"
            />
            
            <div
                v-if="isLoading"
                class="absolute right-3 top-1/2 -translate-y-1/2"
            >
                <div class="animate-spin h-5 w-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
            </div>
        </div>

        <div
            v-if="showSuggestions && suggestions.length > 0"
            class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-80 overflow-y-auto"
        >
            <div
                v-for="(medicine, index) in suggestions"
                :key="medicine.id"
                :class="[
                    'px-4 py-3 cursor-pointer hover:bg-gray-100 transition-colors',
                    index === selectedIndex ? 'bg-blue-50' : ''
                ]"
                @click="selectMedicine(medicine)"
                @mouseenter="selectedIndex = index"
            >
                <div class="font-medium text-gray-900">{{ medicine.nom_commercial }}</div>
                <div class="text-sm text-gray-600">{{ medicine.dci }}</div>
                <div class="text-xs text-gray-500">{{ medicine.forme }} - {{ medicine.dosage }}</div>
            </div>
        </div>

        <div
            v-if="showSuggestions && !isLoading && suggestions.length === 0 && searchQuery.length >= 2"
            class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg px-4 py-3 text-gray-600"
        >
            Aucun résultat trouvé
        </div>
    </div>
</template>
