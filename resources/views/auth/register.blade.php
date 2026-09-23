@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
<div class="max-w-sm mx-auto bg-white p-6 rounded-xl shadow-sm border" x-data="registerForm()">
    <h1 class="text-xl font-bold mb-4">Buat Akun</h1>

    <template x-if="errorMessage" x-cloak>
        <div class="bg-red-50 text-red-700 text-sm p-3 rounded-lg mb-3 whitespace-pre-line" x-text="errorMessage"></div>
    </template>

    <form @submit.prevent="submit()" class="space-y-3">
        <div>
            <label class="text-sm font-medium">Nama</label>
            <input type="text" x-model="form.name" required maxlength="255" class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>
        <div>
            <label class="text-sm font-medium">Email</label>
            <input type="email" x-model="form.email" required class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>
        <div>
            <label class="text-sm font-medium">No. HP</label>
            <input type="tel" x-model="form.phone" maxlength="20" class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>
        <div>
            <label class="text-sm font-medium">Daftar sebagai</label>
            <select x-model="form.role" class="w-full border rounded-lg px-3 py-2 mt-1">
                <option value="customer">Customer</option>
                <option value="provider">Provider</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Akun provider perlu persetujuan admin sebelum dapat mengelola resource.</p>
        </div>
        <div>
            <label class="text-sm font-medium">Password</label>
            <input type="password" x-model="form.password" required minlength="8" class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>
        <div>
            <label class="text-sm font-medium">Konfirmasi Password</label>
            <input type="password" x-model="form.password_confirmation" required minlength="8" class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>

        <button type="submit" :disabled="loading" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
            <span x-text="loading ? 'Mendaftarkan...' : 'Daftar'"></span>
        </button>
    </form>

    <p class="text-sm text-gray-500 mt-4">Sudah punya akun? <a href="{{ url('/login') }}" class="text-blue-600 hover:underline">Login</a></p>
</div>

@push('scripts')
<script>
function registerForm() {
    return {
        form: { name: '', email: '', phone: '', role: 'customer', password: '', password_confirmation: '' },
        loading: false,
        errorMessage: null,
        async submit() {
            this.loading = true;
            this.errorMessage = null;
            try {
                const data = await apiFetch('/register', { method: 'POST', body: this.form });
                this.$root.setAuth(data.token, data.user);
                window.location.href = data.user.role === 'provider' && data.user.provider_status === 'active'
                    ? '{{ url('/provider/dashboard') }}'
                    : '{{ url('/resources') }}';
            } catch (e) {
                this.errorMessage = e.data?.errors
                    ? Object.values(e.data.errors).flat().join('\n')
                    : e.message;
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
@endsection
