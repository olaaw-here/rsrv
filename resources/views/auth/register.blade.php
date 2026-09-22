@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="max-w-sm mx-auto bg-white p-6 rounded-xl shadow-sm border"
     x-data="loginForm()">

    <h1 class="text-xl font-bold mb-4">Login</h1>

    <template x-if="errorMessage" x-cloak>
        <div class="bg-red-50 text-red-700 text-sm p-3 rounded-lg mb-3" x-text="errorMessage"></div>
    </template>

    <form @submit.prevent="submit()" class="space-y-3">
        <div>
            <label class="text-sm font-medium">Email</label>
            <input type="email" x-model="form.email" required
                   class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>
        <div>
            <label class="text-sm font-medium">Password</label>
            <input type="password" x-model="form.password" required
                   class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>

        <button type="submit" :disabled="loading"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
            <span x-text="loading ? 'Memproses...' : 'Login'"></span>
        </button>
    </form>

    <p class="text-sm text-gray-500 mt-4">
        Belum punya akun? <a href="{{ url('/register') }}" class="text-blue-600 hover:underline">Daftar di sini</a>
    </p>
</div>

@push('scripts')
<script>
    function loginForm() {
        return {
            form: { email: '', password: '' },
            loading: false,
            errorMessage: null,

            async submit() {
                this.loading = true;
                this.errorMessage = null;

                try {
                    const data = await apiFetch('/login', { method: 'POST', body: this.form });

                    // Simpan token via Alpine store yang ada di layout (root $data)
                    this.$root.setAuth(data.token, data.user);

                    window.location.href = data.user.role === 'provider'
                        ? '{{ url('/provider/dashboard') }}'
                        : '{{ url('/resources') }}';
                } catch (e) {
                    this.errorMessage = e.message;
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>
@endpush
@endsection
