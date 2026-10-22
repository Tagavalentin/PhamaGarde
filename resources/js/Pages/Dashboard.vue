<script setup>
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth?.user;
</script>

<template>
    <Head title="Tableau de bord" />

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Tableau de bord</h1>

            <div v-if="user" class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-medium text-gray-900 mb-4">Bienvenue, {{ user.name }}</h2>
                <p class="text-gray-600">Rôle : {{ user.role }}</p>
                <p class="text-gray-600">Email : {{ user.email }}</p>
            </div>

            <div v-else class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-600">Veuillez vous connecter pour accéder à votre tableau de bord.</p>
            </div>
        </div>
    </div>
</template>
