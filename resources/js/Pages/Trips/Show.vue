<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet icon issue
import icon from 'leaflet/dist/images/marker-icon.png';
import iconShadow from 'leaflet/dist/images/marker-shadow.png';

let DefaultIcon = L.icon({
    iconUrl: icon,
    shadowUrl: iconShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
});

L.Marker.prototype.options.icon = DefaultIcon;

const props = defineProps({
    trip: Object,
    members: Array,
    destinations: Array,
    userRole: String,
    isAdmin: Boolean,
});

const activeTab = ref('overview');
const showDestinationModal = ref(false);

const destinationForm = useForm({
    name: '',
    category: 'attraction',
    description: '',
    location: '',
    visit_date: '',
    start_time: '',
    end_time: '',
    estimated_cost: '',
    latitude: '',
    longitude: '',
});

const showInviteModal = ref(false);
const inviteForm = useForm({
    email: '',
    role: 'member',
});

const submitInvite = () => {
    inviteForm.post(route('trips.members.store', props.trip.id), {
        onSuccess: () => {
            showInviteModal.value = false;
            inviteForm.reset();
        },
    });
};

const showPromoteModal = ref(false);
const memberToPromote = ref(null);

const promoteMember = (member) => {
    memberToPromote.value = member;
    showPromoteModal.value = true;
};

const confirmPromote = () => {
    if (memberToPromote.value) {
        router.patch(route('trips.members.update', [props.trip.id, memberToPromote.value.trip_member_id]), {
            role: 'admin'
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showPromoteModal.value = false;
                memberToPromote.value = null;
            }
        });
    }
};

const modalMapContainer = ref(null);

const initModalMap = () => {
    // Wait for modal transition/rendering
    let attempts = 0;
    const tryInit = () => {
        const container = document.getElementById('modal-map');
        if (container) {
             // If map already exists, just resize it
            if (modalMapInstance.value) {
                modalMapInstance.value.invalidateSize();
                return;
            }

            // Default to Borobudur or existing coords
            let center = [-7.6079, 110.2038]; 
            if (destinationForm.latitude && destinationForm.longitude) {
                center = [destinationForm.latitude, destinationForm.longitude];
            }

            modalMapInstance.value = L.map('modal-map').setView(center, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(modalMapInstance.value);

            modalMapInstance.value.on('click', (e) => {
                addMarkerToModalMap(e.latlng.lat, e.latlng.lng);
            });
            
            if (destinationForm.latitude && destinationForm.longitude) {
                addMarkerToModalMap(destinationForm.latitude, destinationForm.longitude);
            }
            
            // Force resize after init
            setTimeout(() => {
                modalMapInstance.value.invalidateSize();
            }, 200);

        } else if (attempts < 20) {
            attempts++;
            setTimeout(tryInit, 100);
        }
    };
    
    // Start trying
    tryInit();
};

const categories = {
    attraction: '🏛️ Atraksi',
    food: '🍽️ Kuliner',
    transport: '🚗 Transportasi',
    accommodation: '🏨 Penginapan',
    other: '📍 Lainnya',
};

const mapInstance = ref(null);
const modalMapInstance = ref(null);
const itineraryMapInstance = ref(null);
const itineraryLayerGroup = ref(null);
const modalMarker = ref(null);
const searchQuery = ref('');
const isSearching = ref(false);
const addMarkerToModalMap = (lat, lng) => {
    destinationForm.latitude = lat;
    destinationForm.longitude = lng;

    if (modalMarker.value) {
        modalMarker.value.setLatLng([lat, lng]);
    } else {
        modalMarker.value = L.marker([lat, lng], { draggable: true }).addTo(modalMapInstance.value);
        modalMarker.value.on('dragend', (event) => {
            const position = event.target.getLatLng();
            destinationForm.latitude = position.lat;
            destinationForm.longitude = position.lng;
        });
    }
    
    modalMapInstance.value.setView([lat, lng], 15);
};

const searchLocation = async () => {
    if (!searchQuery.value) return;
    
    isSearching.value = true;
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}`);
        const data = await response.json();
        
        if (data && data.length > 0) {
            const result = data[0];
            const lat = parseFloat(result.lat);
            const lon = parseFloat(result.lon);
            
            addMarkerToModalMap(lat, lon);
            
            // Auto fill location name if empty
            if (!destinationForm.location) {
                destinationForm.location = result.display_name.split(',')[0];
            }
        } else {
            alert('Lokasi tidak ditemukan');
        }
    } catch (error) {
        console.error('Error searching location:', error);
        alert('Gagal mencari lokasi');
    } finally {
        isSearching.value = false;
    }
};

watch(showDestinationModal, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            initModalMap();
        });
    } else {
        // Cleanup map when modal closes to save resources? 
        // Or just leave it for next time.
        // For now let's leave it, but maybe reset marker if needed.
    }
});

const fetchRoute = async (coordinates) => {
    // coordinates: [[lat, lng], [lat, lng], ...]
    if (coordinates.length < 2) return null;

    // Timeout after 3 seconds to ensure fallback runs if API is slow
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 3000);

    // OSRM requires lon,lat format
    const waypoints = coordinates.map(c => `${c[1]},${c[0]}`).join(';');
    const url = `https://router.project-osrm.org/route/v1/driving/${waypoints}?overview=full&geometries=geojson`;

    try {
        const response = await fetch(url, { signal: controller.signal });
        const data = await response.json();
        
        if (data.code === 'Ok' && data.routes.length > 0) {
            return data.routes[0].geometry;
        }
    } catch (error) {
        console.warn('Routing API failed or timed out, using fallback straight lines.', error);
    } finally {
        clearTimeout(timeoutId);
    }
    return null;
};

const initItineraryMap = async () => {
    const container = document.getElementById('itinerary-map');
    if (!container) return;

    if (!itineraryMapInstance.value) {
        itineraryMapInstance.value = L.map('itinerary-map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(itineraryMapInstance.value);
        
        itineraryLayerGroup.value = L.layerGroup().addTo(itineraryMapInstance.value);
    }

    // Clear existing layers
    itineraryLayerGroup.value.clearLayers();

    // Get valid destinations and sort by date/time
    const validDestinations = props.destinations
        .filter(d => d.latitude && d.longitude)
        .sort((a, b) => {
            const dateCompare = (a.visit_date || '').localeCompare(b.visit_date || '');
            if (dateCompare !== 0) return dateCompare;
            return (a.start_time || '').localeCompare(b.start_time || '');
        });

    if (validDestinations.length > 0) {
        const latlngs = [];
        validDestinations.forEach(dest => {
            // Ensure numeric coordinates
            const lat = parseFloat(dest.latitude);
            const lng = parseFloat(dest.longitude);
            const latlng = [lat, lng];
            latlngs.push(latlng);
            
            const marker = L.marker(latlng)
                .bindPopup(`
                    <div class="text-center">
                        <strong class="font-bold text-gray-800">${dest.name}</strong><br/>
                        <span class="text-xs text-gray-500">${dest.visit_date}</span>
                    </div>
                `);
            itineraryLayerGroup.value.addLayer(marker);
        });

        if (latlngs.length > 1) {
            // OPTIMISTIC UI: Render straight line immediately (Fallback/Loading state)
            const straightLine = L.polyline(latlngs, { 
                color: '#9ca3af', // Gray
                weight: 3, 
                opacity: 0.5, 
                dashArray: '5, 10' 
            }).addTo(itineraryLayerGroup.value);
            
            itineraryMapInstance.value.fitBounds(straightLine.getBounds(), { padding: [50, 50] });

            try {
                 // Try to fetch road route
                const routeGeometry = await fetchRoute(latlngs);
                
                // Determine if we are still on the same "frame" or if map was cleared?
                // Actually `itineraryLayerGroup` clearing handles the race condition somewhat.
                
                if (routeGeometry) {
                    itineraryLayerGroup.value.removeLayer(straightLine);
                    
                    const routeLayer = L.geoJSON(routeGeometry, {
                        style: { color: '#6366f1', weight: 4, opacity: 0.8, dashArray: '10, 10' }
                    }).addTo(itineraryLayerGroup.value);
                    
                    itineraryMapInstance.value.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
                } else {
                    // Keep straight line, style as final
                    straightLine.setStyle({ color: '#6366f1', opacity: 0.8, dashArray: null });
                }
            } catch (e) {
                // Keep straight line, style as final
                 straightLine.setStyle({ color: '#6366f1', opacity: 0.8, dashArray: null });
            }
        } else {
             const bounds = L.latLngBounds(latlngs);
             itineraryMapInstance.value.fitBounds(bounds, { padding: [50, 50] });
             itineraryMapInstance.value.setZoom(13); // Manual zoom if only 1 marker
        }

    } else {
        // Default view (Indonesia)
        itineraryMapInstance.value.setView([-2.5489, 118.0149], 5);
    }
    
    // Fix resize issues
    setTimeout(() => {
        itineraryMapInstance.value.invalidateSize();
    }, 200);
};

watch(activeTab, (newTab) => {
    if (newTab === 'itinerary') {
        nextTick(() => {
            initItineraryMap();
        });
    }
});

watch(() => props.destinations, (newVal) => {
    console.log('Props Destinations Updated:', newVal);
    console.table(newVal.map(d => ({ name: d.name, lat: d.latitude, lng: d.longitude }))); // Table view for clarity
    if (itineraryMapInstance.value && activeTab.value === 'itinerary') {
        initItineraryMap();
    }
}, { deep: true });

const editingDestination = ref(null);

const openAddModal = () => {
    editingDestination.value = null;
    destinationForm.reset();
    showDestinationModal.value = true;
};

const editDestination = (dest) => {
    console.log('Editing destination:', dest); // Debug prop data
    editingDestination.value = dest;
    destinationForm.name = dest.name;
    destinationForm.category = dest.category;
    destinationForm.description = dest.description || '';
    destinationForm.location = dest.location || '';
    destinationForm.visit_date = dest.visit_date;
    destinationForm.start_time = dest.start_time ? dest.start_time.substring(0, 5) : '';
    destinationForm.end_time = dest.end_time ? dest.end_time.substring(0, 5) : '';
    destinationForm.estimated_cost = dest.estimated_cost;
    destinationForm.latitude = dest.latitude;
    destinationForm.longitude = dest.longitude;
    
    showDestinationModal.value = true;
};

const submitDestination = () => {
    // Ensure coordinates are numbers
    if (destinationForm.latitude) destinationForm.latitude = parseFloat(destinationForm.latitude);
    if (destinationForm.longitude) destinationForm.longitude = parseFloat(destinationForm.longitude);

    if (editingDestination.value) {
        destinationForm.patch(route('trips.destinations.update', [props.trip.id, editingDestination.value.id]), {
            onSuccess: () => {
                showDestinationModal.value = false;
                destinationForm.reset();
                editingDestination.value = null;
                if (modalMarker.value) {
                    modalMarker.value.remove();
                    modalMarker.value = null;
                }
                if (modalMapInstance.value) {
                    modalMapInstance.value.remove();
                    modalMapInstance.value = null;
                }
            },
        });
    } else {
        destinationForm.post(route('trips.destinations.store', props.trip.id), {
            onSuccess: () => {
                showDestinationModal.value = false;
                destinationForm.reset();
                if (modalMarker.value) {
                    modalMarker.value.remove();
                    modalMarker.value = null;
                }
                if (modalMapInstance.value) {
                    modalMapInstance.value.remove();
                    modalMapInstance.value = null;
                }
            },
        });
    }
};

const deleteDestination = (dest) => {
    if (confirm(`Apakah Anda yakin ingin menghapus destinasi ${dest.name}?`)) {
        router.delete(route('trips.destinations.destroy', [props.trip.id, dest.id]), {
            onSuccess: () => {
                if (editingDestination.value && editingDestination.value.id === dest.id) {
                    showDestinationModal.value = false;
                    editingDestination.value = null;
                }
                // Map will auto-update via watcher
            },
        });
    }
};

const tabs = [
    { id: 'overview', label: 'Overview', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { id: 'itinerary', label: 'Itinerary', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
    { id: 'finance', label: 'Keuangan', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'members', label: 'Anggota', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
];

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatShortDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });
};

const daysUntilTrip = computed(() => {
    const startDate = new Date(props.trip.start_date);
    const today = new Date();
    const diffTime = startDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
});

const tripDuration = computed(() => {
    const start = new Date(props.trip.start_date);
    const end = new Date(props.trip.end_date);
    const diffTime = end - start;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

const getStatusColor = (status) => {
    const colors = {
        planning: 'bg-blue-500',
        saving: 'bg-amber-500',
        ongoing: 'bg-green-500',
        completed: 'bg-gray-500',
    };
    return colors[status] || colors.planning;
};

const getCategoryIcon = (category) => {
    const icons = {
        attraction: '🏛️',
        food: '🍽️',
        transport: '🚗',
        accommodation: '🏨',
        other: '📍',
    };
    return icons[category] || icons.other;
};

const groupedDestinations = computed(() => {
    const groups = {};
    props.destinations.forEach(dest => {
        if (!groups[dest.visit_date]) {
            groups[dest.visit_date] = [];
        }
        groups[dest.visit_date].push(dest);
    });
    return groups;
});

const totalEstimatedCost = computed(() => {
    return props.destinations.reduce((sum, dest) => sum + (dest.estimated_cost || 0), 0);
});


</script>

<template>
    <Head :title="trip.name" />

    <AuthenticatedLayout :full-width="true">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('trips.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            {{ trip.name }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            {{ trip.destination }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="isAdmin"
                        :href="route('trips.edit', trip.id)"
                        class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </Link>
                </div>
            </div>
        </template>
        
        <div class="min-h-screen relative">

                <!-- Parallax Hero Image -->
                <div class="fixed inset-x-0 top-16 z-0 h-[30vh] md:h-[40vh]">
                    <div class="absolute inset-0 bg-gradient-to-t from-background-light/80 to-transparent z-10 dark:from-background-dark/80"></div>
                     <img 
                        v-if="trip.cover_image"
                        :src="trip.cover_image" 
                        :alt="trip.name" 
                        class="w-full h-full object-cover"
                    />
                     <img 
                        v-else
                        src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80"
                        alt="Default Cover"
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Spacer for Parallax -->
                <div class="h-[25vh] md:h-[35vh]"></div>

                <!-- Main Content Sheet -->
                <div class="relative z-10 bg-background-light dark:bg-background-dark rounded-t-3xl shadow-top min-h-screen -mt-6">
                    <!-- Tabs (Sticky) -->
                    <div class="sticky top-16 z-20 bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-md rounded-t-3xl border-b border-gray-100 dark:border-gray-800">
                        <nav class="flex gap-2 overflow-x-auto px-4 py-3 no-scrollbar" aria-label="Tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    activeTab === tab.id
                                        ? 'bg-primary text-white shadow-glow'
                                        : 'bg-white dark:bg-gray-800 text-text-sub-light dark:text-text-sub-dark hover:bg-gray-50 dark:hover:bg-gray-700',
                                    'flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold transition-all duration-300'
                                ]"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                                </svg>
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <div class="px-0 sm:px-0 lg:px-0 py-6">
                        <!-- Moved Hero Info (Now 'Header Info') -->
                        <div class="mb-8 px-4 sm:px-6 lg:px-8">
                             <div class="flex items-start justify-between gap-4 mb-4">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span :class="[getStatusColor(trip.status), 'px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider text-white']">
                                            {{ trip.status }}
                                        </span>
                                        <span v-if="isAdmin" class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider">
                                            Admin
                                        </span>
                                    </div>
                                    <h1 class="text-2xl md:text-3xl font-bold text-text-main-light dark:text-text-main-dark font-display leading-tight mb-2">{{ trip.name }}</h1>
                                    <p class="text-sm text-text-sub-light dark:text-text-sub-dark flex items-center gap-1">
                                        <span class="material-symbols-rounded text-lg">calendar_month</span>
                                        {{ formatDate(trip.start_date) }} - {{ formatDate(trip.end_date) }}
                                    </p>
                                </div>
                             </div>

                             <!-- Linear Progress Bar -->
                            <div class="bg-card-light dark:bg-card-dark p-5 rounded-2xl shadow-soft dark:border dark:border-gray-800">
                                <div class="flex justify-between items-end mb-2">
                                    <div>
                                        <p class="text-xs text-text-sub-light dark:text-text-sub-dark font-medium mb-1">Total Tabungan</p>
                                        <p class="text-lg font-bold text-primary">{{ formatCurrency(trip.total_savings) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs text-text-sub-light dark:text-text-sub-dark">Target: {{ formatCurrency(trip.target_amount) }}</span>
                                        <p class="text-sm font-bold text-primary">{{ Math.round(trip.savings_progress) }}%</p>
                                    </div>
                                </div>
                                <div class="relative w-full h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="absolute top-0 left-0 h-full bg-gradient-to-r from-primary to-purple-400 rounded-full transition-all duration-1000 ease-out"
                                        :style="{ width: `${trip.savings_progress}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>



                <!-- Tab Content -->
                <div v-show="activeTab === 'overview'" class="space-y-6 px-4 sm:px-6 lg:px-8">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30">
                                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Terkumpul</span>
                            </div>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(trip.total_savings) }}</p>
                        </div>

                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Target</span>
                            </div>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(trip.target_amount) }}</p>
                        </div>

                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 rounded-lg bg-red-100 dark:bg-red-900/30">
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Pengeluaran</span>
                            </div>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(trip.total_expenses) }}</p>
                        </div>

                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Saldo</span>
                            </div>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(trip.remaining_balance) }}</p>
                        </div>
                    </div>

                    <!-- New Overview Widgets: Itinerary Preview & Members -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Itinerary Preview -->
                        <div class="bg-white dark:bg-card-dark rounded-2xl p-5 shadow-soft border border-gray-100 dark:border-gray-800">
                             <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-text-main-light dark:text-text-main-dark">Rundown Singkat</h3>
                                <button @click="activeTab = 'itinerary'" class="text-xs text-primary font-medium hover:underline">Lihat Semua</button>
                            </div>
                            <div v-if="destinations.length > 0" class="space-y-4">
                                <div v-for="dest in destinations.slice(0, 3)" :key="dest.id" class="flex gap-3 items-start">
                                    <div class="mt-0.5 p-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400">
                                        <span class="text-lg">{{ getCategoryIcon(dest.category) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-text-main-light dark:text-text-main-dark line-clamp-1">{{ dest.name }}</p>
                                        <p class="text-xs text-text-sub-light dark:text-text-sub-dark">{{ formatDate(dest.visit_date) }}</p>
                                    </div>
                                </div>
                            </div>
                             <div v-else class="text-center py-6">
                                <p class="text-sm text-text-sub-light dark:text-text-sub-dark italic">Belum ada destinasi</p>
                            </div>
                        </div>

                        <!-- Members Preview -->
                        <div class="bg-white dark:bg-card-dark rounded-2xl p-5 shadow-soft border border-gray-100 dark:border-gray-800">
                             <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-text-main-light dark:text-text-main-dark">Anggota Trip</h3>
                                <button @click="activeTab = 'members'" class="text-xs text-primary font-medium hover:underline">Kelola</button>
                            </div>
                             <div class="flex items-center ml-2">
                                <div class="flex -space-x-3">
                                    <div v-for="member in members.slice(0, 5)" :key="member.id" class="w-10 h-10 rounded-full border-2 border-white dark:border-card-dark bg-gray-200 flex items-center justify-center overflow-hidden" :title="member.name">
                                         <img 
                                            v-if="member.avatar_url" 
                                            :src="member.avatar_url" 
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else class="text-xs font-bold text-gray-600">{{ member.name.charAt(0) }}</span>
                                    </div>
                                    <div v-if="members.length > 5" class="w-10 h-10 rounded-full border-2 border-white dark:border-card-dark bg-primary flex items-center justify-center text-white text-xs font-bold z-10">
                                        +{{ members.length - 5 }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex gap-4 text-sm text-text-sub-light dark:text-text-sub-dark">
                                <div>
                                    <span class="block font-bold text-text-main-light dark:text-text-main-dark">{{ members.length }}</span>
                                    <span>Total</span>
                                </div>
                                <div class="border-l border-gray-200 dark:border-gray-700 pl-4">
                                    <span class="block font-bold text-green-600">{{ members.filter(m => m.roll === 'admin').length || 1 }}</span>
                                    <span>Admin</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="trip.description" class="rounded-xl bg-white dark:bg-card-dark p-6 shadow-soft border border-gray-100 dark:border-gray-800">
                        <h3 class="text-lg font-bold text-text-main-light dark:text-text-main-dark mb-3">Tentang Trip Ini</h3>
                        <p class="text-text-sub-light dark:text-text-sub-dark leading-relaxed">{{ trip.description }}</p>
                    </div>
                </div>

                <!-- Itinerary Tab -->
                <div v-show="activeTab === 'itinerary'" class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rundown Perjalanan</h3>
                        <button 
                            @click="openAddModal"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition w-full sm:w-auto"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Destinasi
                        </button>
                    </div>

                    <!-- Itinerary Map -->
                    <div class="rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <div id="itinerary-map" class="h-64 sm:h-80 w-full z-0"></div>
                        <div class="p-3 bg-white dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 text-center border-t border-gray-100 dark:border-gray-700">
                            Peta rute perjalanan berdasarkan urutan destinasi
                        </div>
                    </div>

                    <div v-if="destinations.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 px-4 sm:px-6 lg:px-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">Belum ada itinerary</h3>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">Mulai susun rencana perjalanan dengan menambah destinasi.</p>
                    </div>

                    <!-- Grouped by Date -->
                    <div v-else class="space-y-6">
                        <div v-for="(dests, date) in groupedDestinations" :key="date" class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 px-6 py-3 border-b border-gray-100 dark:border-gray-700">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ formatDate(date) }}</h4>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div v-for="dest in dests" :key="dest.id" class="p-4 flex items-start gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <span class="text-2xl">{{ getCategoryIcon(dest.category) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ dest.name }}</h5>
                                            <span v-if="!dest.latitude || !dest.longitude" class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded-full flex items-center gap-1" title="Lokasi belum diatur, tidak akan muncul di peta">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                No Loc
                                            </span>
                                        </div>
                                        <p v-if="dest.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ dest.description }}</p>
                                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span v-if="dest.start_time" class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ dest.start_time }} - {{ dest.end_time || '?' }}
                                            </span>
                                            <span v-if="dest.location" class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                </svg>
                                                {{ dest.location }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right flex flex-col items-end gap-1">
                                        <div v-if="dest.estimated_cost">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(dest.estimated_cost) }}</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button 
                                                @click="editDestination(dest)" 
                                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition"
                                            >
                                                Edit
                                            </button>
                                            <button 
                                                @click="deleteDestination(dest)" 
                                                class="text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Estimated -->
                    <div v-if="destinations.length > 0" class="rounded-xl bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 p-6 border border-indigo-100 dark:border-indigo-800">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Total Estimasi Biaya</span>
                            <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalEstimatedCost) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Finance Tab -->
                <div v-show="activeTab === 'finance'" class="space-y-6 px-4 sm:px-6 lg:px-8">
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Terkumpul</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(trip.total_savings) }}</p>
                        </div>
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Target</p>
                            <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(trip.target_amount) }}</p>
                        </div>
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pengeluaran</p>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ formatCurrency(trip.total_expenses) }}</p>
                        </div>
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Saldo</p>
                            <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ formatCurrency(trip.remaining_balance) }}</p>
                        </div>
                    </div>

                    <!-- Finance Actions -->
                    <div class="grid gap-4 sm:grid-cols-3">
                        <Link 
                            :href="route('trips.savings.index', trip.id)"
                            class="rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-white hover:from-green-600 hover:to-emerald-700 transition shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white/20 rounded-lg">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-lg">Tabungan</h4>
                                    <p class="text-sm text-green-100">Lihat & bayar tabungan</p>
                                </div>
                            </div>
                        </Link>

                        <Link 
                            :href="route('trips.expenses.index', trip.id)"
                            class="rounded-xl bg-gradient-to-r from-red-500 to-pink-600 p-6 text-white hover:from-red-600 hover:to-pink-700 transition shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white/20 rounded-lg">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-lg">Pengeluaran</h4>
                                    <p class="text-sm text-red-100">Catat pengeluaran trip</p>
                                </div>
                            </div>
                        </Link>

                        <Link 
                            :href="route('trips.withdrawals.index', trip.id)"
                            class="rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 p-6 text-white hover:from-purple-600 hover:to-indigo-700 transition shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white/20 rounded-lg">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-lg">Penarikan</h4>
                                    <p class="text-sm text-purple-100">Request & voting</p>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Progress Bar -->
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-300">Progress Tabungan</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ Math.round(trip.savings_progress) }}%</span>
                        </div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full transition-all duration-500"
                                :style="{ width: `${trip.savings_progress}%` }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Members Tab -->
                <div v-show="activeTab === 'members'" class="space-y-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">👥 Anggota Trip</h3>
                        <button 
                            v-if="isAdmin" 
                            @click="showInviteModal = true"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Undang Anggota
                        </button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="member in members" :key="member.id" class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-lg">
                                    {{ member.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ member.name }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ member.email }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span 
                                        :class="[
                                            member.role === 'admin' 
                                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' 
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                            'rounded-full px-2 py-1 text-xs font-medium'
                                        ]"
                                    >
                                        {{ member.role === 'admin' ? 'Admin' : 'Member' }}
                                    </span>
                                    
                                    <button 
                                        v-if="isAdmin && member.role !== 'admin'"
                                        @click="promoteMember(member)"
                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition"
                                    >
                                        Jadikan Admin
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- Close Main Padding Div -->
            </div> <!-- Close Content Sheet Div -->

        <!-- Destination Modal -->
        <Teleport to="body">
            <div v-if="showDestinationModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDestinationModal = false" />
                    <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                        <form @submit.prevent="submitDestination">
                            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ editingDestination ? 'Edit Destinasi' : 'Tambah Destinasi' }}</h3>
                            </div>
                            <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
                                <div>
                                    <InputLabel for="dest_name" value="Nama Destinasi" />
                                    <TextInput id="dest_name" v-model="destinationForm.name" type="text" class="mt-1 block w-full" placeholder="contoh: Candi Borobudur" required />
                                    <InputError :message="destinationForm.errors.name" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="dest_category" value="Kategori" />
                                    <select id="dest_category" v-model="destinationForm.category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                    <InputError :message="destinationForm.errors.category" class="mt-2" />
                                </div>

                                <!-- Map Picker & Location Search -->
                                <div class="space-y-2">
                                    <InputLabel value="Cari Lokasi / Tandai di Peta" />
                                    <div class="flex gap-2">
                                        <input 
                                            v-model="searchQuery" 
                                            type="text" 
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300"
                                            placeholder="Cari lokasi (contoh: Monas)" 
                                            @keyup.enter.prevent="searchLocation"
                                        />
                                        <button 
                                            type="button"
                                            @click="searchLocation"
                                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition disabled:opacity-50"
                                            :disabled="isSearching"
                                        >
                                            {{ isSearching ? '...' : 'Cari' }}
                                        </button>
                                    </div>
                                    
                                    <div class="rounded-lg overflow-hidden border border-gray-300 dark:border-gray-700 relative">
                                        <div id="modal-map" class="h-64 w-full z-0"></div>
                                        <div v-if="!destinationForm.latitude" class="absolute inset-0 flex items-center justify-center bg-gray-100/50 dark:bg-gray-800/50 backdrop-blur-sm z-[400] pointer-events-none">
                                            <span class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 px-3 py-1 rounded-full shadow">
                                                Klik peta atau cari lokasi
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Lat/Lng Inputs (Read-only/Hidden visually if preferred, but useful for verification) -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <InputLabel value="Latitude" class="text-xs" />
                                            <TextInput v-model="destinationForm.latitude" type="text" class="mt-1 block w-full text-xs bg-gray-50 dark:bg-gray-800" readonly placeholder="Latitude" />
                                            <InputError :message="destinationForm.errors.latitude" class="mt-1" />
                                        </div>
                                        <div>
                                            <InputLabel value="Longitude" class="text-xs" />
                                            <TextInput v-model="destinationForm.longitude" type="text" class="mt-1 block w-full text-xs bg-gray-50 dark:bg-gray-800" readonly placeholder="Longitude" />
                                            <InputError :message="destinationForm.errors.longitude" class="mt-1" />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="dest_location" value="Nama Lokasi / Alamat" />
                                    <TextInput id="dest_location" v-model="destinationForm.location" type="text" class="mt-1 block w-full" />
                                    <InputError :message="destinationForm.errors.location" class="mt-2" />
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="col-span-1">
                                        <InputLabel for="dest_date" value="Tanggal" />
                                        <TextInput 
                                            id="dest_date" 
                                            v-model="destinationForm.visit_date" 
                                            type="date" 
                                            class="mt-1 block w-full" 
                                            :min="trip.start_date" 
                                            :max="trip.end_date" 
                                            required 
                                        />
                                        <InputError :message="destinationForm.errors.visit_date" class="mt-2 text-xs" />
                                    </div>
                                    <div>
                                        <InputLabel for="dest_start" value="Mulai" />
                                        <TextInput id="dest_start" v-model="destinationForm.start_time" type="time" class="mt-1 block w-full" />
                                    </div>
                                    <div>
                                        <InputLabel for="dest_end" value="Selesai" />
                                        <TextInput id="dest_end" v-model="destinationForm.end_time" type="time" class="mt-1 block w-full" />
                                    </div>
                                </div>
                                <InputError :message="destinationForm.errors.start_time" class="mt-1" />
                                <InputError :message="destinationForm.errors.end_time" class="mt-1" />

                                <div>
                                    <InputLabel for="dest_cost" value="Estimasi Biaya (Opsional)" />
                                    <TextInput id="dest_cost" v-model="destinationForm.estimated_cost" type="number" class="mt-1 block w-full" min="0" />
                                    <InputError :message="destinationForm.errors.estimated_cost" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="dest_desc" value="Deskripsi (Opsional)" />
                                    <textarea id="dest_desc" v-model="destinationForm.description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                                    <InputError :message="destinationForm.errors.description" class="mt-2" />
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3">
                                <button type="button" @click="showDestinationModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</button>
                                <PrimaryButton :disabled="destinationForm.processing">{{ destinationForm.processing ? 'Menyimpan...' : 'Simpan' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Invite Member Modal -->
        <Teleport to="body">
            <div v-if="showInviteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showInviteModal = false" />
                    <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:align-middle">
                        <form @submit.prevent="submitInvite">
                            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Undang Anggota Baru</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <InputLabel for="invite_email" value="Email Pengguna" />
                                    <TextInput 
                                        id="invite_email" 
                                        v-model="inviteForm.email" 
                                        type="email" 
                                        class="mt-1 block w-full" 
                                        placeholder="email@contoh.com" 
                                        required 
                                        autofocus
                                    />
                                    <InputError :message="inviteForm.errors.email" class="mt-2" />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Pastikan user sudah terdaftar di aplikasi ini.
                                    </p>
                                </div>
                                <div>
                                    <InputLabel for="invite_role" value="Role" />
                                    <select 
                                        id="invite_role" 
                                        v-model="inviteForm.role" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="member">Anggota (Member)</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                    <InputError :message="inviteForm.errors.role" class="mt-2" />
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3">
                                <button 
                                    type="button" 
                                    @click="showInviteModal = false" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition"
                                >
                                    Batal
                                </button>
                                <PrimaryButton :disabled="inviteForm.processing">
                                    {{ inviteForm.processing ? 'Mengirim...' : 'Kirim Undangan' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Promote Admin Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showPromoteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPromoteModal = false" />
                    <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:align-middle">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">Promosikan Anggota</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Apakah Anda yakin ingin menjadikan <strong>{{ memberToPromote?.name }}</strong> sebagai Admin?
                                            Admin dapat mengelola trip, destinasi, dan keuangan.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3 sm:flex-row-reverse">
                            <button 
                                type="button" 
                                @click="confirmPromote" 
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto"
                            >
                                Ya, Jadikan Admin
                            </button>
                            <button 
                                type="button" 
                                @click="showPromoteModal = false" 
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Leaflet Map */
#itinerary-map, #modal-map {
    width: 100%;
    z-index: 1;
}

/* Fix z-index for Leaflet controls */
.leaflet-control-container {
    z-index: 2;
}

/* Ensure map attribution doesn't overlay modal actions */
.leaflet-bottom {
    z-index: 2;
}
</style>
