import { ref } from 'vue';

export function useRequests() {
    const requests = ref([]);
    const request = ref(null);
    const packageVersion = ref('...');
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        offset: 0,
        limit: 50,
        total: 0
    });

    const activeFilters = ref({
        method: [],
        status_code: [],
        url: '',
        ip_address: '',
        user_id: '',
        date_from: '',
        date_to: '',
        duration_min: '',
        duration_max: ''
    });

    const stats = ref({
        total_requests: 0,
        error_rate: 0,
        avg_latency: 0,
        active_users: 0
    });

    const fetchStats = async () => {
        loading.value = true;
        try {
            const response = await fetch('/api-watcher/api/stats', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            if (!response.ok) throw new Error('Failed to fetch stats');
            stats.value = await response.json();
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    };

    const fetchRequests = async (filters = null) => {
        if (filters) {
            if (filters.limit) {
                pagination.value.limit = filters.limit;
                delete filters.limit;
            }
            activeFilters.value = { ...activeFilters.value, ...filters };
        }

        loading.value = true;
        error.value = null;
        try {
            const queryParams = new URLSearchParams({
                limit: pagination.value.limit,
                offset: pagination.value.offset
            });

            const f = activeFilters.value;
            if (f.url) queryParams.append('url', f.url);
            if (f.ip_address) queryParams.append('ip_address', f.ip_address);
            if (f.user_id) queryParams.append('user_id', f.user_id);
            if (f.date_from) queryParams.append('date_from', f.date_from);
            if (f.date_to) queryParams.append('date_to', f.date_to);
            if (f.duration_min) queryParams.append('duration_min', f.duration_min);
            if (f.duration_max) queryParams.append('duration_max', f.duration_max);

            if (f.method && f.method.length) {
                f.method.forEach(m => queryParams.append('method[]', m));
            }
            if (f.status_code && f.status_code.length) {
                f.status_code.forEach(s => queryParams.append('status_code[]', s));
            }

            const query = queryParams.toString();

            // In a real app, base URL should come from config or window object injected by Blade
            const response = await fetch(`/api-watcher/api/requests?${query}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) throw new Error('Failed to fetch requests');

            const data = await response.json();
            requests.value = data;
            // Assuming the API returns simple array for now, pagination handling needs total count from API
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    };

    const fetchRequest = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api-watcher/api/requests/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) throw new Error('Failed to fetch request details');

            request.value = await response.json();
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    };

    const replayRequest = async (originalRequest) => {
        loading.value = true;
        error.value = null;
        try {
            const url = originalRequest.url;
            const options = {
                method: originalRequest.method,
                headers: originalRequest.request_headers,
            };

            if (originalRequest.method !== 'GET' && originalRequest.method !== 'HEAD') {
                options.body = typeof originalRequest.request_body === 'string'
                    ? originalRequest.request_body
                    : JSON.stringify(originalRequest.request_body);
            }

            const response = await fetch(url, options);

            // We need to clone to read body text and still declare it as JSON if possible
            const clone = response.clone();
            let responseData;
            try {
                responseData = await response.json();
            } catch {
                responseData = await clone.text();
            }

            const headers = {};
            response.headers.forEach((value, key) => {
                headers[key] = value;
            });

            return {
                status: response.status,
                statusText: response.statusText,
                headers: headers,
                data: responseData
            };
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const fetchAnalytics = async (days = 30) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api-watcher/api/analytics?days=${days}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            if (!response.ok) throw new Error('Failed to fetch analytics');
            return await response.json();
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const fetchConfig = async () => {
        try {
            const response = await fetch('/api-watcher/api/config', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.version) packageVersion.value = `v${data.version}`;
            return data;
        } catch (e) {
            console.error('Failed to fetch config', e);
            return {};
        }
    };

    const triggerPrune = async (days) => {
        loading.value = true;
        try {
            const response = await fetch('/api-watcher/api/actions/prune', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ days })
            });
            if (!response.ok) throw new Error('Failed to prune requests');
            return await response.json();
        } catch (e) {
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const triggerClear = async () => {
        loading.value = true;
        try {
            const response = await fetch('/api-watcher/api/actions/clear', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            if (!response.ok) throw new Error('Failed to clear requests');
            return await response.json();
        } catch (e) {
            throw e;
        } finally {
            loading.value = false;
        }
    };

    return {
        requests,
        request,
        loading,
        error,
        pagination,
        activeFilters,
        stats,
        packageVersion,
        fetchRequests,
        fetchRequest,
        fetchStats,
        fetchAnalytics,
        replayRequest,
        fetchConfig,
        triggerPrune,
        triggerClear
    };
}
