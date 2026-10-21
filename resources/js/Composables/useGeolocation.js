import { ref } from 'vue';

export function useGeolocation() {
    const location = ref(null);
    const error = ref(null);
    const isLoading = ref(false);

    const LOME_DEFAULT = {
        lat: 6.1250,
        lng: 1.2167,
    };

    const getUserLocation = () => {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                error.value = 'La géolocalisation n\'est pas supportée par votre navigateur';
                resolve(LOME_DEFAULT);
                return;
            }

            isLoading.value = true;
            error.value = null;

            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            };

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    location.value = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };
                    isLoading.value = false;
                    resolve(location.value);
                },
                (err) => {
                    isLoading.value = false;
                    switch (err.code) {
                        case err.PERMISSION_DENIED:
                            error.value = 'La permission de géolocalisation a été refusée';
                            break;
                        case err.POSITION_UNAVAILABLE:
                            error.value = 'Les informations de position ne sont pas disponibles';
                            break;
                        case err.TIMEOUT:
                            error.value = 'La demande de position a expiré';
                            break;
                        default:
                            error.value = 'Une erreur inconnue est survenue';
                    }
                    resolve(LOME_DEFAULT);
                },
                options
            );
        });
    };

    const setLocation = (lat, lng) => {
        location.value = { lat, lng };
        error.value = null;
    };

    const clearLocation = () => {
        location.value = null;
        error.value = null;
    };

    return {
        location,
        error,
        isLoading,
        getUserLocation,
        setLocation,
        clearLocation,
        LOME_DEFAULT,
    };
}
