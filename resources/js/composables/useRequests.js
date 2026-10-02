import { ref } from 'vue';

export function useRequests() {
    const requests = ref([]);
    const request = ref(null);
    const packageVersion = ref('v2.1.0');
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        page: 1,
        per_page: 50,
        total: 0,
        last_page: 1
    });

    const activeFilters = ref({
        search: '',
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

    const fetchRequests = async (filters = null, page = null) => {
        if (page !== null) {
            pagination.value.page = page;
        }

        if (filters) {
            if (filters.per_page) {
                pagination.value.per_page = filters.per_page;
                delete filters.per_page;
            }
            if (filters.page) {
                pagination.value.page = filters.page;
                delete filters.page;
            }
            activeFilters.value = { ...activeFilters.value, ...filters };
        }

        loading.value = true;
        error.value = null;
        try {
            const queryParams = new URLSearchParams({
                page: pagination.value.page,
                per_page: pagination.value.per_page,
            });

            const f = activeFilters.value;
            if (f.search && f.search.trim()) queryParams.append('search', f.search.trim());
            if (f.url && f.url.trim()) queryParams.append('url', f.url.trim());
            if (f.ip_address && f.ip_address.trim()) queryParams.append('ip_address', f.ip_address.trim());
            if (f.user_id !== undefined && f.user_id !== null && f.user_id !== '') queryParams.append('user_id', f.user_id);
            if (f.date_from) queryParams.append('date_from', f.date_from);
            if (f.date_to) queryParams.append('date_to', f.date_to);
            if (f.duration_min !== '' && f.duration_min !== null && f.duration_min !== undefined) queryParams.append('duration_min', f.duration_min);
            if (f.duration_max !== '' && f.duration_max !== null && f.duration_max !== undefined) queryParams.append('duration_max', f.duration_max);

            if (f.method && f.method.length) {
                f.method.forEach(m => queryParams.append('method[]', m));
            }
            if (f.status_code && f.status_code.length) {
                f.status_code.forEach(s => queryParams.append('status_code[]', s));
            }

            const query = queryParams.toString();

            const response = await fetch(`/api-watcher/api/requests?${query}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) throw new Error('Failed to fetch requests');

            const result = await response.json();

            if (Array.isArray(result)) {
                requests.value = result;
                pagination.value.total = result.length;
                pagination.value.last_page = 1;
            } else if (result && Array.isArray(result.data)) {
                requests.value = result.data;
                pagination.value.total = result.total ?? result.data.length;
                pagination.value.page = result.page ?? pagination.value.page;
                pagination.value.per_page = result.per_page ?? pagination.value.per_page;
                pagination.value.last_page = result.last_page ?? 1;
            } else {
                requests.value = [];
            }
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
            if (data.version && data.version !== 'unknown') {
                packageVersion.value = data.version.startsWith('v') ? data.version : `v${data.version}`;
            }
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

    const changePage = (page) => {
        if (page < 1 || (pagination.value.last_page && page > pagination.value.last_page)) return;
        fetchRequests(null, page);
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
        changePage,
        fetchRequest,
        fetchStats,
        fetchAnalytics,
        replayRequest,
        fetchConfig,
        triggerPrune,
        triggerClear
    };
}
